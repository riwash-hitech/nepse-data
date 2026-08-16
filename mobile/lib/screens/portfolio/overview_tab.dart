import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';
import '../../services/api_client.dart';
import '../stock_detail_screen.dart';

class OverviewTab extends StatefulWidget {
  const OverviewTab({super.key});

  @override
  State<OverviewTab> createState() => OverviewTabState();
}

class OverviewTabState extends State<OverviewTab> with AutomaticKeepAliveClientMixin {
  final _api = ApiClient.instance;
  Map<String, dynamic>? _overview;
  bool _loading = true;
  String? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.get('/portfolio/overview');
      setState(() {
        _overview = Map<String, dynamic>.from(res);
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
    super.build(context);
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text(_error!));
    return RefreshIndicator(onRefresh: load, child: _buildBody(_overview!));
  }

  Widget _buildBody(Map<String, dynamic> o) {
    final rows = (o['top_holdings'] as List?) ?? [];
    final unrealized = _num(o['unrealized']) ?? 0;
    final realized = _num(o['realized']) ?? 0;
    final dayGL = _num(o['day_gain_loss']) ?? 0;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 2.2,
          children: [
            _statTile('Investment', o['investment']),
            _statTile('Market Value', o['market_value']),
            _statTile('Day G/L', o['day_gain_loss'], colored: true, value: dayGL),
            _statTile('Unrealized G/L', o['unrealized'], colored: true, value: unrealized),
            _statTile('Realized G/L', o['realized'], colored: true, value: realized),
            _statTile('Stocks Held', o['stock_count'], isCount: true),
          ],
        ),
        const SizedBox(height: 20),
        if ((o['sector_totals'] as Map?)?.isNotEmpty == true) ...[
          const Text('Sector Allocation', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          ..._sectorBars(o['sector_totals'], _num(o['market_value']) ?? 0),
          const SizedBox(height: 20),
        ],
        const Text('Top Holdings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        const SizedBox(height: 8),
        if (rows.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Center(child: Text('No holdings yet. Tap + to record a buy.')),
          )
        else
          ...rows.map((r) {
            final gain = _num(r['unrealized_gain']) ?? 0;
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                title: Text(r['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                subtitle: Text('${r['quantity']} shares @ avg ${_fmt(r['avg_cost'])}'),
                trailing: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(_fmt(r['market_value']), style: const TextStyle(fontWeight: FontWeight.w600)),
                    Text(
                      '${gain >= 0 ? '+' : ''}${_fmt(r['unrealized_gain'])}',
                      style: TextStyle(fontSize: 12, color: gain >= 0 ? AppColors.up : AppColors.down),
                    ),
                  ],
                ),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: r['symbol'])),
                ),
              ),
            );
          }),
      ],
    );
  }

  List<Widget> _sectorBars(Map sectorTotals, double total) {
    if (total <= 0) return [];
    final entries = sectorTotals.entries.toList();
    return entries.map((e) {
      final value = _num(e.value) ?? 0;
      final pct = value / total;
      return Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(e.key.toString(), style: const TextStyle(fontSize: 13)),
                Text('${(pct * 100).toStringAsFixed(1)}%', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
            const SizedBox(height: 4),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: pct.clamp(0, 1),
                minHeight: 6,
                backgroundColor: AppColors.border,
                valueColor: const AlwaysStoppedAnimation(AppColors.accent),
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  Widget _statTile(String label, dynamic raw, {bool colored = false, double? value, bool isCount = false}) {
    final display = isCount ? '${raw ?? 0}' : _fmt(raw);
    Color? color;
    if (colored && value != null) {
      color = value >= 0 ? AppColors.up : AppColors.down;
    }
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          const SizedBox(height: 4),
          Text(display, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: color ?? AppColors.textPrimary)),
        ],
      ),
    );
  }

  double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());
  String _fmt(dynamic v) {
    final n = _num(v);
    return n == null ? '—' : n.toStringAsFixed(2);
  }
}
