import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';

/// Price chart with a period selector — fetches its own data per period from
/// the existing /stocks/{symbol}/chart endpoint.
class PriceChart extends StatefulWidget {
  final String symbol;
  const PriceChart({super.key, required this.symbol});

  @override
  State<PriceChart> createState() => _PriceChartState();
}

class _PriceChartState extends State<PriceChart> {
  final _api = ApiClient.instance;
  static const _periods = ['1W', '1M', '3M', '1Y', 'ALL'];
  String _period = '3M';

  List<dynamic>? _points;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.get('/stocks/${widget.symbol}/chart', {'period': _period});
      setState(() {
        _points = res as List?;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Price Chart', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          Row(
            children: _periods.map((p) {
              final selected = p == _period;
              return Padding(
                padding: const EdgeInsets.only(right: 6),
                child: GestureDetector(
                  onTap: () {
                    setState(() => _period = p);
                    _load();
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: selected ? AppColors.brand : AppColors.surfaceAlt,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(p,
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: selected ? Colors.white : AppColors.textSecondary)),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 200,
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.textSecondary, fontSize: 12)))
                    : (_points == null || _points!.length < 2)
                        ? const Center(child: Text('Not enough data to chart.', style: TextStyle(color: AppColors.textSecondary, fontSize: 12)))
                        : _buildChart(_points!),
          ),
        ],
      ),
    );
  }

  Widget _buildChart(List<dynamic> points) {
    final closes = points.map((p) => (p['close'] as num).toDouble()).toList();
    final minY = closes.reduce((a, b) => a < b ? a : b);
    final maxY = closes.reduce((a, b) => a > b ? a : b);
    final pad = (maxY - minY) * 0.08 + 0.01;
    final isUp = closes.last >= closes.first;
    final lineColor = isUp ? AppColors.up : AppColors.down;

    final spots = List.generate(closes.length, (i) => FlSpot(i.toDouble(), closes[i]));

    return LineChart(
      LineChartData(
        minY: minY - pad,
        maxY: maxY + pad,
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          horizontalInterval: (maxY - minY + pad * 2) / 4,
          getDrawingHorizontalLine: (_) => const FlLine(color: AppColors.border, strokeWidth: 1),
        ),
        titlesData: FlTitlesData(
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          bottomTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 44,
              interval: (maxY - minY + pad * 2) / 4,
              getTitlesWidget: (v, meta) => Text(v.toStringAsFixed(0),
                  style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
            ),
          ),
        ),
        borderData: FlBorderData(show: false),
        lineTouchData: LineTouchData(
          touchTooltipData: LineTouchTooltipData(
            getTooltipColor: (_) => AppColors.brand,
            getTooltipItems: (spots) => spots.map((s) {
              return LineTooltipItem(s.y.toStringAsFixed(2), const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11));
            }).toList(),
          ),
        ),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: false,
            color: lineColor,
            barWidth: 2,
            dotData: const FlDotData(show: false),
            belowBarData: BarAreaData(show: true, color: lineColor.withValues(alpha: 0.12)),
          ),
        ],
      ),
    );
  }
}
