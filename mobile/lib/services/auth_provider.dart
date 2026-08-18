import 'package:flutter/foundation.dart';
import 'api_client.dart';

class AuthProvider extends ChangeNotifier {
  final _api = ApiClient.instance;

  bool _loading = true;
  bool _authed = false;
  String? _name;
  String? _email;
  bool _isAdmin = false;
  bool _emailVerified = true;
  String? _error;

  bool get loading => _loading;
  bool get isAuthenticated => _authed;
  String? get name => _name;
  String? get email => _email;
  bool get isAdmin => _isAdmin;
  bool get emailVerified => _emailVerified;
  String? get error => _error;

  Future<void> bootstrap() async {
    // Minimum splash duration so the welcome screen doesn't just flash by
    // when the backend responds instantly (e.g. on localhost).
    final minSplash = Future.delayed(const Duration(milliseconds: 1400));

    await _api.loadPersisted();
    if (_api.isAuthenticated) {
      try {
        final me = await _api.get('/me');
        _authed = true;
        _name = me['name'];
        _email = me['email'];
        _isAdmin = me['is_admin'] ?? false;
        _emailVerified = me['email_verified'] ?? true;
      } catch (_) {
        await _api.setToken(null);
        _authed = false;
      }
    }

    await minSplash;
    _loading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _error = null;
    try {
      final res = await _api.post('/login', {'email': email, 'password': password});
      await _api.setToken(res['token']);
      _authed = true;
      _name = res['user']['name'];
      _email = res['user']['email'];
      _isAdmin = res['user']['is_admin'] ?? false;
      _emailVerified = res['user']['email_verified'] ?? true;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      // Defense in depth: an unexpected error shape (e.g. a malformed
      // response) should never leave the caller's "signing in..." spinner
      // stuck forever with no feedback.
      _error = 'Something went wrong. Please try again.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> register(String email, String phone, String password, String passwordConfirmation) async {
    _error = null;
    try {
      final res = await _api.post('/register', {
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      });
      await _api.setToken(res['token']);
      _authed = true;
      _name = res['user']['name'];
      _email = res['user']['email'];
      _isAdmin = res['user']['is_admin'] ?? false;
      _emailVerified = res['user']['email_verified'] ?? true;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      _error = 'Something went wrong. Please try again.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> forgotPassword(String email) async {
    _error = null;
    try {
      await _api.post('/forgot-password', {'email': email});
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      _error = 'Something went wrong. Please try again.';
      notifyListeners();
      return false;
    }
  }

  /// Re-checks verification status with the server (e.g. after the user taps
  /// the link in their email and comes back to the app).
  Future<void> refreshVerificationStatus() async {
    try {
      final me = await _api.get('/me');
      _emailVerified = me['email_verified'] ?? true;
      notifyListeners();
    } catch (_) {}
  }

  Future<bool> resendVerification() async {
    _error = null;
    try {
      await _api.post('/email/verification-notification');
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      _error = 'Something went wrong. Please try again.';
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/logout');
    } catch (_) {}
    await _api.setToken(null);
    _authed = false;
    _name = null;
    _email = null;
    _emailVerified = true;
    notifyListeners();
  }
}
