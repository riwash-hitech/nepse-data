import 'package:flutter/material.dart';

/// Riwash Money brand palette — light, green-accented.
class AppColors {
  const AppColors._();

  static const bg = Color(0xFFF3F6F4);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceAlt = Color(0xFFEDF4EF);
  static const border = Color(0xFFE1E8E3);
  static const brand = Color(0xFF14532D);
  static const accent = Color(0xFF16A34A);
  static const accentDim = Color(0xFFDCFCE7);
  static const textPrimary = Color(0xFF10171A);
  static const textSecondary = Color(0xFF6B7684);
  static const up = Color(0xFF16A34A);
  static const upBg = Color(0xFFDCFCE7);
  static const down = Color(0xFFDC2626);
  static const downBg = Color(0xFFFEE2E2);
}

class AppTheme {
  const AppTheme._();

  static ThemeData get light {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      fontFamily: 'monospace',
      scaffoldBackgroundColor: AppColors.bg,
      colorScheme: const ColorScheme.light(
        primary: AppColors.accent,
        secondary: AppColors.brand,
        surface: AppColors.surface,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.bg,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        centerTitle: false,
      ),
      cardColor: AppColors.surface,
      dividerColor: AppColors.border,
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.surface,
        indicatorColor: AppColors.accentDim,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontFamily: 'monospace',
            fontSize: 11,
            fontWeight: selected ? FontWeight.bold : FontWeight.normal,
            color: selected ? AppColors.accent : AppColors.textSecondary,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(color: selected ? AppColors.accent : AppColors.textSecondary);
        }),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        hintStyle: const TextStyle(color: AppColors.textSecondary, fontFamily: 'monospace'),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.accent),
        ),
      ),
      textTheme: const TextTheme().apply(
        fontFamily: 'monospace',
        bodyColor: AppColors.textPrimary,
        displayColor: AppColors.textPrimary,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.accent,
          foregroundColor: Colors.white,
          textStyle: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold),
        ),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: ButtonStyle(
          backgroundColor: WidgetStateProperty.resolveWith((states) {
            return states.contains(WidgetState.selected) ? AppColors.brand : AppColors.surface;
          }),
          foregroundColor: WidgetStateProperty.resolveWith((states) {
            return states.contains(WidgetState.selected) ? Colors.white : AppColors.textSecondary;
          }),
          side: WidgetStateProperty.all(const BorderSide(color: AppColors.border)),
        ),
      ),
    );
  }
}
