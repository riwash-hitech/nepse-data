import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

enum AppEnvironment { test, production }

class AppConfig {
  const AppConfig._();

  static const appName = 'Riwash Money';

  /// Selected at build/run time, e.g.:
  ///   flutter run --dart-define=APP_ENV=test          (default — local backend)
  ///   flutter run --dart-define=APP_ENV=production    (live nepse.riwash.com backend)
  static const _envName = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'production',
  );

  static AppEnvironment get environment {
    switch (_envName) {
      case 'production':
        return AppEnvironment.production;
      default:
        return AppEnvironment.test;
    }
  }

  static const _apiPathSuffix = '/api';

  static const _productionHost = 'https://nepse.riwash.com';

  /// Port `php artisan serve --port=...` is running on locally.
  static const _testPort = '8000';

  /// Explicit override for the local/test backend host, e.g. for a physical
  /// device on the same WiFi network as your dev machine:
  ///   flutter run --dart-define=APP_ENV=test --dart-define=TEST_HOST=192.168.1.23:8010
  /// (also start Laravel with `php artisan serve --host=0.0.0.0 --port=8010` so
  /// it's reachable from other devices on the network, not just this machine.)
  static const _testHostOverride = String.fromEnvironment('TEST_HOST');

  static String get _testHost {
    if (_testHostOverride.isNotEmpty) {
      return _testHostOverride.startsWith('http')
          ? _testHostOverride
          : 'http://$_testHostOverride';
    }

    // localhost inside the Android emulator refers to the emulator itself,
    // not the host machine running the backend — 10.0.2.2 is Android's
    // documented alias for the host's loopback in that case. This does NOT
    // help a physical device; use TEST_HOST above for that.
    if (!kIsWeb) {
      try {
        if (Platform.isAndroid) return 'http://10.0.2.2:$_testPort';
      } catch (_) {}
    }

    return 'http://localhost:$_testPort';
  }

  /// Host only (no API path) — used for building non-API links.
  static String get websiteBaseUrl {
    switch (environment) {
      case AppEnvironment.production:
        return _productionHost;
      case AppEnvironment.test:
        return _testHost;
    }
  }

  /// Host + API path prefix. Same path suffix in both environments — only
  /// the host changes.
  static String get apiBaseUrl => '$websiteBaseUrl$_apiPathSuffix';

  static bool get isProduction => environment == AppEnvironment.production;
}
