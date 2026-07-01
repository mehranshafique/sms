import 'package:flutter/material.dart';

/// Digitex brand theme — aligned with web SMS (purple primary, clean cards).
class AppTheme {
  static const Color primary = Color(0xFF5C4DFF);
  static const Color primaryDark = Color(0xFF4527A0);
  static const Color accent = Color(0xFFFFB800);
  static const Color surface = Color(0xFFF4F5FA);
  static const Color card = Colors.white;
  static const Color textPrimary = Color(0xFF1E1E2D);
  static const Color textMuted = Color(0xFF6E6E8A);

  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primary,
        primary: primary,
        secondary: accent,
        surface: surface,
      ),
      scaffoldBackgroundColor: surface,
      appBarTheme: const AppBarTheme(
        backgroundColor: card,
        foregroundColor: textPrimary,
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardTheme(
        color: card,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: card,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE8E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE8E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: primary, width: 2),
        ),
      ),
    );
    return base;
  }
}
