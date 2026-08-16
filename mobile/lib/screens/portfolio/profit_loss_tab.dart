import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';
import '../../services/api_client.dart';
import '../stock_detail_screen.dart';

class ProfitLossTab extends StatefulWidget {
  const ProfitLossTab({super.key});

  @override
  State<ProfitLossTab> createState() => ProfitLossTabState();
}

class ProfitLossTabState extends State<ProfitLossTab> with AutomaticKeepAliveClientMixin {
  final _api = ApiClient.instance;
  Map<String, dynamic>? _data;
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
      final res = await _api.get('/portfolio/holdings');
      final data = Map<String, dynamic>.from(res);
      final rows = List<dynamic>.from(data['rows'] ?? []);
      rows.sort((a, b) => (_num(b['unrealized_gain']) ?? 0).compareTo(_num(a['unrealized_gain']) ?? 0));
      data['rows'] = rows;
      setState(() {
        _data = data;
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

    final rows = (_data!['rows'] as List?) ?? [];
    final investment = _num(_data!['investment']) ?? 0;
    final unrealized = _num(_data!['unrealized']) ?? 0;

    return RefreshIndicator(
      onRefresh: load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _summaryStat('Invested', investment, colored: false),
                _summaryStat('Unrealized G/L', unrealized, colored: true),
              ],
            ),
          ),
          const SizedBox(height: 16),
          if (rows.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(vertical: 40), child: Center(child: Text('No holdings to show P/L for.')))
          else
            ...rows.map((r) {
              final gain = _num(r['unrealized_gain']) ?? 0;
              final cost = _num(r['investment']) ?? 0;
              final gainPct = cost > 0 ? (gain / cost * 100) : 0.0;
              final isUp = gain >= 0;
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(r['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                  subtitle: Text('Cost ${_fmt(r['investment'])}  →  Value ${_fmt(r['market_value'])}'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text('${isUp ? '+' : ''}${_fmt(r['unrealized_gain'])}',
                          style: TextStyle(fontWeight: FontWeight.bold, color: isUp ? AppColors.up : AppColors.down)),
                      Text('${isUp ? '+' : ''}${gainPct.toStringAsFixed(1)}%',
                          style: TextStyle(fontSize: 12, color: isUp ? AppColors.up : AppColors.down)),
                    ],
                  ),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: r['symbol'])),
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _summaryStat(String label, double value, {required bool colored}) {
    final color = colored ? (value >= 0 ? AppColors.up : AppColors.down) : AppColors.textPrimary;
    return Column(
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
        const SizedBox(height: 4),
        Text(value.toStringAsFixed(2), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: color)),
      ],
    );
  }

  double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());
  String _fmt(dynamic v) {
    final n = _num(v);
    return n == null ? '—' : n.toStringAsFixed(2);
  }
}
