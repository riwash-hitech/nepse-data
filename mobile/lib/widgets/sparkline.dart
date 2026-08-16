import 'package:flutter/material.dart';

/// Minimal inline trend line — draws [values] (already normalized-free, raw
/// numbers) as a single polyline. Used for the "24H" column in Top Movers,
/// where showing an actual chart would be overkill for a list row.
class Sparkline extends StatelessWidget {
  final List<double> values;
  final Color color;
  final double width;
  final double height;

  const Sparkline({
    super.key,
    required this.values,
    required this.color,
    this.width = 56,
    this.height = 24,
  });

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size(width, height),
      painter: _SparklinePainter(values: values, color: color),
    );
  }
}

class _SparklinePainter extends CustomPainter {
  final List<double> values;
  final Color color;

  _SparklinePainter({required this.values, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    if (values.length < 2) return;

    final minV = values.reduce((a, b) => a < b ? a : b);
    final maxV = values.reduce((a, b) => a > b ? a : b);
    final range = (maxV - minV).abs() < 1e-9 ? 1.0 : (maxV - minV);

    final path = Path();
    for (int i = 0; i < values.length; i++) {
      final x = size.width * i / (values.length - 1);
      final normalized = (values[i] - minV) / range;
      final y = size.height - (normalized * size.height);
      if (i == 0) {
        path.moveTo(x, y);
      } else {
        path.lineTo(x, y);
      }
    }

    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.6
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _SparklinePainter oldDelegate) {
    return oldDelegate.values != values || oldDelegate.color != color;
  }
}
