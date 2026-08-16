import 'package:flutter/material.dart';

/// Colored circle with a stock's first two letters — a stable color per
/// symbol (hash-based), so the same stock always gets the same color.
class StockAvatar extends StatelessWidget {
  final String symbol;
  final double size;

  const StockAvatar({super.key, required this.symbol, this.size = 36});

  static const _palette = [
    Color(0xFF2563EB), // blue
    Color(0xFFC026D3), // magenta
    Color(0xFFCA8A04), // amber
    Color(0xFF0D9488), // teal
    Color(0xFF9333EA), // purple
    Color(0xFFDC2626), // red
    Color(0xFF16A34A), // green
    Color(0xFFEA580C), // orange
  ];

  @override
  Widget build(BuildContext context) {
    final letters = symbol.length >= 2 ? symbol.substring(0, 2) : symbol;
    final color = _palette[symbol.codeUnits.fold(0, (a, b) => a + b) % _palette.length];

    return CircleAvatar(
      radius: size / 2,
      backgroundColor: color.withValues(alpha: 0.15),
      child: Text(
        letters.toUpperCase(),
        style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: size * 0.32),
      ),
    );
  }
}
