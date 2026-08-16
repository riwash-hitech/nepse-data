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
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        itemCount: rows.length + 1,
        separatorBuilder: (_, i) => const Divider(height: 1),
        itemBuilder: (context, i) {
          if (i == rows.length) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(_fmt(d['market_value']), style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
            );
          }
          final r = rows[i];
          final changePct = _num(r['change_percent']) ?? 0;
          final gain = _num(r['unrealized_gain']) ?? 0;
          final isUp = changePct >= 0;
          return ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(r['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text('${r['quantity']} @ avg ${_fmt(r['avg_cost'])}  ·  LTP ${_fmt(r['ltp'])} (${isUp ? '+' : ''}${changePct.toStringAsFixed(2)}%)'),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(_fmt(r['market_value']), style: const TextStyle(fontWeight: FontWeight.w600)),
                Text('${gain >= 0 ? '+' : ''}${_fmt(r['unrealized_gain'])}',
                    style: TextStyle(fontSize: 12, color: gain >= 0 ? AppColors.up : AppColors.down)),
              ],
            ),
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: r['symbol'])),
            ),
          );
        },
      ),
    );
  }

  double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());
  String _fmt(dynamic v) {
    final n = _num(v);
    return n == null ? '—' : n.toStringAsFixed(2);
  }
}
