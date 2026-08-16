import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';
import '../widgets/stock_avatar.dart';
import 'stock_detail_screen.dart';

class AllMoversScreen extends StatefulWidget {
  final String initialType;
  const AllMoversScreen({super.key, this.initialType = 'gainers'});

  @override
  State<AllMoversScreen> createState() => _AllMoversScreenState();
}

class _AllMoversScreenState extends State<AllMoversScreen> {
  final _api = ApiClient.instance;
  late String _type = widget.initialType;
  List<dynamic> _rows = [];
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
      final res = await _api.get('/dashboard/movers', {'type': _type, 'limit': 50});
      setState(() {
        _rows = (res as List?) ?? [];
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Top Movers'),
        actions: [IconButton(icon: const Icon(Icons.refresh), onPressed: _load, tooltip: 'Refresh live data')],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Row(
              children: [
                _pill('Gainers', 'gainers'),
                const SizedBox(width: 8),
                _pill('Losers', 'losers'),
                const SizedBox(width: 8),
                _pill('Turnover', 'turnover'),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.textSecondary)))
                    : RefreshIndicator(
                        onRefresh: _load,
                        child: ListView.separated(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _rows.length,
                          separatorBuilder: (_, i) => const Divider(height: 1),
                          itemBuilder: (context, i) {
                            final m = _rows[i];
                            final changePct = (m['change_percent'] as num).toDouble();
                            final isUp = changePct >= 0;
                            final color = isUp ? AppColors.up : AppColors.down;
                            final bg = isUp ? AppColors.upBg : AppColors.downBg;
                            return ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: StockAvatar(symbol: m['symbol'] ?? ''),
                              title: Text(m['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: _type == 'turnover'
                                  ? Text('Vol ${(m['volume'] as num).toStringAsFixed(0)}', style: const TextStyle(color: AppColors.textSecondary, fontSize: 12))
                                  : null,
                              trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text((m['ltp'] as num).toStringAsFixed(2), style: const TextStyle(fontWeight: FontWeight.w600)),
                                  const SizedBox(height: 4),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(isUp ? Icons.arrow_upward : Icons.arrow_downward, size: 12, color: color),
                                        Text('${changePct.abs().toStringAsFixed(1)}%', style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: m['symbol'])),
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _pill(String label, String value) {
    final selected = _type == value;
    return Expanded(
      child: OutlinedButton(
        onPressed: () {
          setState(() => _type = value);
          _load();
        },
        style: OutlinedButton.styleFrom(
          backgroundColor: selected ? AppColors.brand : AppColors.surface,
          foregroundColor: selected ? Colors.white : AppColors.textSecondary,
          side: const BorderSide(color: AppColors.border),
          padding: const EdgeInsets.symmetric(vertical: 10),
        ),
        child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
      ),
    );
  }
}
