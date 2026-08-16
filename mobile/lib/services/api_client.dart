import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart' show kDebugMode, debugPrint;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  ApiException(this.message, [this.statusCode]);
  @override
  String toString() => message;
}

/// Thin JSON API client for the NEPSE Laravel backend.
///
/// Which server it talks to (local dev machine vs. nepse.riwash.com) is
/// controlled by [AppConfig.environment] — see lib/config/app_config.dart.
class ApiClient {
  ApiClient._internal();
  static final ApiClient instance = ApiClient._internal();

  String _baseUrl = AppConfig.apiBaseUrl;
  String? _token;

  String get baseUrl => _baseUrl;

  /// Reverts to whatever [AppConfig.apiBaseUrl] currently resolves to,
  /// clearing any manual override saved via [setBaseUrl].
  Future<void> resetBaseUrlToConfig() async {
    _baseUrl = AppConfig.apiBaseUrl;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('base_url');
  }

  Future<void> setBaseUrl(String url) async {
    _baseUrl = url.endsWith('/') ? url.substring(0, url.length - 1) : url;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('base_url', _baseUrl);
  }

  Future<void> loadPersisted() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url') ?? AppConfig.apiBaseUrl;
    _token = prefs.getString('auth_token');
  }

  Future<void> setToken(String? token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    if (token == null) {
      await prefs.remove('auth_token');
    } else {
      await prefs.setString('auth_token', token);
    }
  }

  String? get token => _token;
  bool get isAuthenticated => _token != null;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    Map<String, String>? q;
    if (query != null) {
      q = {};
      query.forEach((k, v) {
        final s = v?.toString() ?? '';
        if (s.isNotEmpty) q![k] = s;
      });
    }
    return Uri.parse('$_baseUrl$path').replace(queryParameters: (q == null || q.isEmpty) ? null : q);
  }

  dynamic _decode(http.Response res) {
    if (res.statusCode >= 200 && res.statusCode < 300) {
      if (res.body.isEmpty) return null;
      return jsonDecode(res.body);
    }
    String message = 'Request failed (${res.statusCode})';
    try {
      final body = jsonDecode(res.body);
      if (body is Map && body['message'] != null) message = body['message'];
      if (body is Map && body['errors'] != null) {
        final errors = (body['errors'] as Map).values.expand((e) => e as List).join(' ');
        message = errors.isNotEmpty ? errors : message;
      }
    } catch (_) {}
    throw ApiException(message, res.statusCode);
  }

  static const _timeout = Duration(seconds: 20);

  /// Every request logs "→ METHOD url", then either "← 200 METHOD url (123ms)"
  /// or "✗ METHOD url failed: <reason> (123ms)" — visible in the `flutter run`
  /// debug console. Only active in debug builds (never in release).
  void _log(String message) {
    if (kDebugMode) debugPrint('[API] $message');
  }

  /// Wraps a raw http call so a stalled connection can't hang the UI
  /// forever, and so every failure mode (timeout, DNS/TLS/socket errors,
  /// malformed responses) surfaces as a catchable [ApiException] instead of
  /// an uncaught exception that leaves a "Signing in..." spinner stuck.
  Future<http.Response> _send(String method, Uri uri, Future<http.Response> Function() request) async {
    _log('→ $method $uri');
    final stopwatch = Stopwatch()..start();
    try {
      final res = await request().timeout(_timeout);
      _log('← ${res.statusCode} $method $uri (${stopwatch.elapsedMilliseconds}ms)');
      return res;
    } on TimeoutException {
      _log('✗ $method $uri timed out after ${stopwatch.elapsedMilliseconds}ms');
      throw ApiException('Could not reach the server — request timed out. Check your connection and try again.');
    } on http.ClientException catch (e) {
      _log('✗ $method $uri failed: ${e.message} (${stopwatch.elapsedMilliseconds}ms)');
      throw ApiException('Could not connect to the server: ${e.message}');
    } on FormatException catch (e) {
      _log('✗ $method $uri returned unparseable response: $e (${stopwatch.elapsedMilliseconds}ms)');
      throw ApiException('The server returned an unexpected response.');
    } catch (e) {
      // Any other connection-level failure (DNS, TLS/socket errors — the
      // exact exception types differ between web and native platforms, so
      // this is a deliberately broad catch-all rather than importing
      // dart:io, which would break the web build).
      _log('✗ $method $uri failed: $e (${stopwatch.elapsedMilliseconds}ms)');
      throw ApiException('Could not reach the server. Check your internet connection and try again.');
    }
  }

  Future<dynamic> get(String path, [Map<String, dynamic>? query]) async {
    final uri = _uri(path, query);
    final res = await _send('GET', uri, () => http.get(uri, headers: _headers));
    return _decode(res);
  }

  Future<dynamic> post(String path, [Map<String, dynamic>? body]) async {
    final uri = _uri(path);
    final res = await _send('POST', uri, () => http.post(uri, headers: _headers, body: jsonEncode(body ?? {})));
    return _decode(res);
  }

  Future<dynamic> delete(String path) async {
    final uri = _uri(path);
    final res = await _send('DELETE', uri, () => http.delete(uri, headers: _headers));
    return _decode(res);
  }
}
