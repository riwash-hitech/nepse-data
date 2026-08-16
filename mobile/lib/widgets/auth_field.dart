import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Labeled input matching the auth screens' style — uppercase bold label,
/// optional trailing widget on the label row (e.g. "Forgot Password?"),
/// icon-prefixed bordered field.
class AuthField extends StatelessWidget {
  final String label;
  final Widget? labelTrailing;
  final TextEditingController controller;
  final IconData icon;
  final String? hint;
  final bool obscureText;
  final Widget? suffixIcon;
  final TextInputType? keyboardType;
  final ValueChanged<String>? onSubmitted;

  const AuthField({
    super.key,
    required this.label,
    required this.controller,
    required this.icon,
    this.labelTrailing,
    this.hint,
    this.obscureText = false,
    this.suffixIcon,
    this.keyboardType,
    this.onSubmitted,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: AppColors.brand,
                letterSpacing: 0.6,
              ),
            ),
            if (labelTrailing != null) labelTrailing!,
          ],
        ),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          obscureText: obscureText,
          keyboardType: keyboardType,
          onSubmitted: onSubmitted,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: Icon(icon, size: 19, color: AppColors.textSecondary),
            suffixIcon: suffixIcon,
          ),
        ),
      ],
    );
  }
}
