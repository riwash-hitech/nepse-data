import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/home_shell.dart';
import 'screens/splash_screen.dart';
import 'screens/verify_email_screen.dart';
import 'theme/app_theme.dart';

void main() {
  runApp(const NepseApp());
}

class NepseApp extends StatelessWidget {
  const NepseApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthProvider()..bootstrap(),
      child: MaterialApp(
        title: 'Riwash Money',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light,
        home: const _Root(),
      ),
    );
  }
}

class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (auth.loading) {
      return const SplashScreen();
    }
    if (!auth.isAuthenticated) {
      return const LoginScreen();
    }
    return auth.emailVerified ? const HomeShell() : const VerifyEmailScreen();
  }
}
