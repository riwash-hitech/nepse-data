import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_provider.dart';
import '../theme/app_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    final ok = await context.read<AuthProvider>().login(_emailCtrl.text.trim(), _passCtrl.text);
    if (mounted) setState(() => _submitting = false);
    if (!ok && mounted) {
      final err = context.read<AuthProvider>().error ?? 'Login failed';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Image.asset('assets/images/logo.png', width: 120),
                  const SizedBox(height: 12),
                  const Text('RIWASH MONEY',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.brand, letterSpacing: 0.5)),
                  const SizedBox(height: 4),
                  const Text('Sign in to manage your holdings',
                      textAlign: TextAlign.center, style: TextStyle(color: AppColors.textSecondary)),
                  const SizedBox(height: 32),
                  TextField(
                    controller: _emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(labelText: 'Email', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _passCtrl,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder()),
                    onSubmitted: (_) => _submit(),
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _submitting ? null : _submit,
                    style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                    child: _submitting
                        ? const SizedBox(
                            height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Sign In'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
