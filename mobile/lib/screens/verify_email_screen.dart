import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/auth_card.dart';

/// Shown instead of the home shell when a logged-in user hasn't clicked the
/// verification link emailed to them yet — every portfolio/watchlist API
/// call 403s until they do, so there's nothing useful to show behind this.
class VerifyEmailScreen extends StatefulWidget {
  const VerifyEmailScreen({super.key});

  @override
  State<VerifyEmailScreen> createState() => _VerifyEmailScreenState();
}

class _VerifyEmailScreenState extends State<VerifyEmailScreen> {
  bool _checking = false;
  bool _resending = false;

  Future<void> _checkAgain() async {
    setState(() => _checking = true);
    await context.read<AuthProvider>().refreshVerificationStatus();
    if (mounted) setState(() => _checking = false);
    if (mounted && !context.read<AuthProvider>().emailVerified) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Still not verified — check your inbox and tap the link first.')),
      );
    }
  }

  Future<void> _resend() async {
    setState(() => _resending = true);
    final ok = await context.read<AuthProvider>().resendVerification();
    if (mounted) setState(() => _resending = false);
    if (mounted) {
      final msg = ok ? 'Verification email sent — check your inbox.' : (context.read<AuthProvider>().error ?? 'Could not resend email.');
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      body: SafeArea(
        child: AuthCard(
          title: 'Verify your email',
          subtitle: 'We sent a verification link to ${auth.email ?? 'your email'}.\nOpen it in your browser, then come back here.',
          children: [
            FilledButton(
              onPressed: _checking ? null : _checkAgain,
              style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
              child: _checking
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text("I'VE VERIFIED — REFRESH", style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 0.5)),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: _resending ? null : _resend,
              style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
              child: _resending
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('RESEND EMAIL', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 0.5)),
            ),
            const SizedBox(height: 20),
            const Divider(color: AppColors.border),
            const SizedBox(height: 16),
            Center(
              child: GestureDetector(
                onTap: () => context.read<AuthProvider>().logout(),
                child: const Text('Log out', style: TextStyle(color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
