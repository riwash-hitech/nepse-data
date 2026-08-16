import 'package:flutter/material.dart';

/// Mini day-over-day bar chart — each bar is green if that day closed above
/// the previous day, muted otherwise, with the most recent bar emphasized.
/// Used under the index cards on the dashboard.
class BarSparkline extends StatelessWidget {
  final List<double> closes;
  final Color upColor;
  final Color mutedColor;
  final double width;
  final double height;

  const BarSparkline({
    super.key,
    required this.closes,
    required this.upColor,
    required this.mutedColor,
    this.width = 140,
    this.height = 36,
  });

  @override
  Widget build(BuildContext context) {
    if (closes.length < 2) return SizedBox(width: width, height: height);

    final minV = closes.reduce((a, b) => a < b ? a : b);
    final maxV = closes.reduce((a, b) => a > b ? a : b);
    final range = (maxV - minV).abs() < 1e-9 ? 1.0 : (maxV - minV);

    return SizedBox(
      width: width,
      height: height,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: List.generate(closes.length, (i) {
          final normalized = (closes[i] - minV) / range;
          final barHeight = (0.2 + normalized * 0.8) * height;
          final isUp = i == 0 || closes[i] >= closes[i - 1];
          final isLast = i == closes.length - 1;
          return Container(
            width: (width / closes.length) - 3,
            height: barHeight,
            decoration: BoxDecoration(
              color: isUp ? upColor.withValues(alpha: isLast ? 1 : 0.55) : mutedColor,
              borderRadius: BorderRadius.circular(2),
            ),
          );
        }),
      ),
    );
  }
}
