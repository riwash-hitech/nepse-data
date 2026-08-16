import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';
import '../../services/api_client.dart';

class TransactionsTab extends StatefulWidget {
  const TransactionsTab({super.key});

  @override
  State<TransactionsTab> createState() => TransactionsTabState();
}

class TransactionsTabState extends State<TransactionsTab> with AutomaticKeepAliveClientMixin {
  final _api = ApiClient.instance;
  List<dynamic> _rows = [];
  bool _loading = true;
  String? _error;
  String _type = '';

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
      final res = await _api.get('/portfolio/transactions', {'type': _type});
      setState(() {
        _rows = (res['data'] as List?) ?? [];
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
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: SegmentedButton<String>(
            segments: const [
              ButtonSegment(value: '', label: Text('All')),
              ButtonSegment(value: 'buy', label: Text('Buy')),
              ButtonSegment(value: 'sell', label: Text('Sell')),
            ],
            selected: {_type},
            onSelectionChanged: (s) {
              setState(() => _type = s.first);
              load();
            },
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(child: Text(_error!))
                  : _rows.isEmpty
                      ? RefreshIndicator(
                          onRefresh: load,
                          child: ListView(children: const [
                            Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: Text('No transactions recorded yet.'))),
                          ]),
                        )
                      : RefreshIndicator(
                          onRefresh: load,
                          child: ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            itemCount: _rows.length,
                            separatorBuilder: (_, i) => const Divider(height: 1),
                            itemBuilder: (context, i) {
                              final t = _rows[i];
                              final stock = t['stock'] ?? {};
                              final isBuy = t['type'] == 'buy';
                              return ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: CircleAvatar(
                                  backgroundColor: (isBuy ? AppColors.up : AppColors.down).withValues(alpha: 0.15),
                                  child: Icon(isBuy ? Icons.add : Icons.remove, color: isBuy ? AppColors.up : AppColors.down),
                                ),
                                title: Text('${stock['symbol'] ?? ''} · ${(t['type'] ?? '').toString().toUpperCase()}',
                                    style: const TextStyle(fontWeight: FontWeight.bold)),
                                subtitle: Text('${t['quantity']} @ ${t['rate']}  ·  ${t['txn_date']}'
                                    '${(t['remarks'] ?? '').toString().isNotEmpty ? '\n${t['remarks']}' : ''}'),
                                isThreeLine: (t['remarks'] ?? '').toString().isNotEmpty,
                              );
                            },
                          ),
                        ),
        ),
      ],
    );
  }
}
