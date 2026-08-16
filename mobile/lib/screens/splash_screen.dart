import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset('assets/images/logo.png', width: 180),
            const SizedBox(height: 20),
            const Text(
              'Welcome to Riwash App',
              style: TextStyle(
                fontFamily: 'monospace',
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.brand,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 28),
            const SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(strokeWidth: 2.4, color: AppColors.accent),
            ),
          ],
        ),
      ),
    );
  }
}
