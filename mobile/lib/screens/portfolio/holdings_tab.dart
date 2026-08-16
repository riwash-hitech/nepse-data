import 'dart:async';
import '../../theme/app_theme.dart';
import 'package:flutter/material.dart';
import '../../services/api_client.dart';
import '../stock_detail_screen.dart';

class HoldingsTab extends StatefulWidget {
  const HoldingsTab({super.key});

  @override
  State<HoldingsTab> createState() => HoldingsTabState();
}

class HoldingsTabState extends State<HoldingsTab> with AutomaticKeepAliveClientMixin {
  final _api = ApiClient.instance;
  final _searchCtrl = TextEditingController();
  Timer? _debounce;

  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;
  String _movement = 'all';

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.get('/portfolio/holdings', {
        'search': _searchCtrl.text.trim(),
        'movement': _movement,
      });
      setState(() {
        _data = Map<String, dynamic>.from(res);
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), load);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: Column(
            children: [
              TextField(
                controller: _searchCtrl,
                onChanged: _onSearchChanged,
                decoration: InputDecoration(
                  hintText: 'Search symbol or company',
                  prefixIcon: const Icon(Icons.search),
                  filled: true,
                  fillColor: AppColors.surface,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                  isDense: true,
                ),
              ),
              const SizedBox(height: 8),
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'all', label: Text('All')),
                  ButtonSegment(value: 'gaining', label: Text('Gaining')),
                  ButtonSegment(value: 'losing', label: Text('Losing')),
                ],
                selected: {_movement},
                onSelectionChanged: (s) {
                  setState(() => _movement = s.first);
                  load();
                },
              ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(child: Text(_error!))
                  : _buildList(_data!),
        ),
      ],
    );
  }

  Widget _buildList(Map<String, dynamic> d) {
    final rows = (d['rows'] as List?) ?? [];
    if (rows.isEmpty) {
      return RefreshIndicator(
        onRefresh: load,
        child: ListView(children: const [
          Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: Text('No holdings match this filter.'))),
        ]),
      );
    }
    return RefreshIndicator(
      onRefresh: load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
        children: [
          ...rows.map((r) => _HoldingCard(
                data: r,
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: r['symbol'])),
                ),
              )),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Total', style: TextStyle(fontWeight: FontWeight.bold)),
                Text(_fmt(d['market_value']), style: const TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
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

class _HoldingCard extends StatelessWidget {
  final Map data;
  final VoidCallback onTap;

  const _HoldingCard({required this.data, required this.onTap});

  double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());
  String _fmt(dynamic v) {
    final n = _num(v);
    return n == null ? '—' : n.toStringAsFixed(2);
  }

  String _fmtVolume(dynamic v) {
    final n = _num(v);
    if (n == null) return '—';
    if (n >= 1000000) return '${(n / 1000000).toStringAsFixed(2)}M';
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}K';
    return n.toStringAsFixed(0);
  }

  @override
  Widget build(BuildContext context) {
    final changePct = _num(data['change_percent']) ?? 0;
    final gain = _num(data['unrealized_gain']) ?? 0;
    final isUp = changePct >= 0;
    final changeColor = isUp ? AppColors.up : AppColors.down;
    final gainColor = gain >= 0 ? AppColors.up : AppColors.down;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(10),
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(data['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textPrimary)),
                          const SizedBox(height: 2),
                          Text('${data['quantity']} @ avg ${_fmt(data['avg_cost'])}',
                              style: const TextStyle(color: AppColors.textSecondary, fontSize: 12)),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(_fmt(data['market_value']), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15, color: AppColors.textPrimary)),
                        const SizedBox(height: 2),
                        Text('${gain >= 0 ? '+' : ''}${_fmt(data['unrealized_gain'])}',
                            style: TextStyle(fontSize: 12, color: gainColor, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Divider(height: 1),
                const SizedBox(height: 10),
                Row(
                  children: [
                    _stat('LTP', _fmt(data['ltp']), valueColor: AppColors.textPrimary),
                    _stat('Change', '${isUp ? '+' : ''}${changePct.toStringAsFixed(2)}%', valueColor: changeColor),
                    _stat('High', _fmt(data['day_high'])),
                    _stat('Low', _fmt(data['day_low'])),
                    _stat('Volume', _fmtVolume(data['volume'])),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _stat(String label, String value, {Color valueColor = AppColors.textPrimary}) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
          const SizedBox(height: 2),
          Text(value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: valueColor)),
        ],
      ),
    );
  }
}
