import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';
import '../../services/api_client.dart';

class RealizedTab extends StatefulWidget {
  const RealizedTab({super.key});

  @override
  State<RealizedTab> createState() => RealizedTabState();
}

class RealizedTabState extends State<RealizedTab> with AutomaticKeepAliveClientMixin {
  final _api = ApiClient.instance;
  List<dynamic> _rows = [];
  double _total = 0;
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
      final res = await _api.get('/portfolio/realized');
      setState(() {
        _rows = (res['transactions']?['data'] as List?) ?? [];
        _total = double.tryParse('${res['total_realized'] ?? 0}') ?? 0;
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

    return RefreshIndicator(
      onRefresh: load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            width: double.infinity,
            decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Total Realized Gain/Loss', style: TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                const SizedBox(height: 4),
                Text(_total.toStringAsFixed(2),
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20, color: _total >= 0 ? AppColors.up : AppColors.down)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          if (_rows.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(vertical: 40), child: Center(child: Text('No sell transactions yet.')))
          else
            ..._rows.map((t) {
              final gain = double.tryParse('${t['realized_gain'] ?? 0}') ?? 0;
              final stock = t['stock'] ?? {};
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(stock['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                  subtitle: Text('${t['quantity']} @ ${t['rate']}  ·  ${t['txn_date']}'),
                  trailing: Text('${gain >= 0 ? '+' : ''}${gain.toStringAsFixed(2)}',
                      style: TextStyle(fontWeight: FontWeight.bold, color: gain >= 0 ? AppColors.up : AppColors.down)),
                ),
              );
            }),
        ],
      ),
    );
  }
}
