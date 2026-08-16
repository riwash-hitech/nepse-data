import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';
import 'stock_detail_screen.dart';

class WatchlistScreen extends StatefulWidget {
  const WatchlistScreen({super.key});

  @override
  State<WatchlistScreen> createState() => _WatchlistScreenState();
}

class _WatchlistScreenState extends State<WatchlistScreen> {
  final _api = ApiClient.instance;
  List<dynamic> _items = [];
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
      final res = await _api.get('/watchlist');
      setState(() {
        _items = res ?? [];
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Future<void> _remove(int stockId) async {
    try {
      await _api.delete('/watchlist/$stockId');
      _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _openAddSymbol() async {
    final added = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: AppColors.surface,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (_) => const _AddSymbolSheet(),
    );
    if (added == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Watchlist'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load, tooltip: 'Refresh live data'),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.textSecondary)))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Expanded(
                            child: Text(
                              'Real-time market tracking',
                              style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
                            ),
                          ),
                          FilledButton.icon(
                            onPressed: _openAddSymbol,
                            icon: const Icon(Icons.add, size: 18),
                            label: const Text('Add Symbol'),
                            style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      if (_items.isEmpty)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 60),
                          child: Center(
                            child: Text(
                              'No stocks in your watchlist yet.\nTap Add Symbol to start tracking one.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: AppColors.textSecondary),
                            ),
                          ),
                        )
                      else
                        ..._items.map((w) => _WatchlistRow(
                              data: w,
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: w['symbol'])),
                              ),
                              onRemove: () => _remove(w['stock_id']),
                            )),
                    ],
                  ),
                ),
    );
  }
}

class _WatchlistRow extends StatelessWidget {
  final Map data;
  final VoidCallback onTap;
  final VoidCallback onRemove;

  const _WatchlistRow({required this.data, required this.onTap, required this.onRemove});

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
    final isUp = changePct >= 0;
    final color = isUp ? AppColors.up : AppColors.down;

    final signalType = data['signal_type']?.toString();
    final confidence = _num(data['confidence']);
    final signalColor = signalType == 'BUY'
        ? AppColors.up
        : signalType == 'SELL'
            ? AppColors.down
            : AppColors.textSecondary;

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
                          Text(data['symbol'] ?? '',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textPrimary)),
                          const SizedBox(height: 2),
                          Text(data['name'] ?? '',
                              style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(_fmt(data['ltp']), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15, color: AppColors.textPrimary)),
                        const SizedBox(height: 2),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(isUp ? Icons.arrow_upward : Icons.arrow_downward, size: 12, color: color),
                            Text('${changePct.abs().toStringAsFixed(2)}%', style: TextStyle(color: color, fontSize: 12)),
                          ],
                        ),
                      ],
                    ),
                    IconButton(
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                      icon: const Icon(Icons.close, size: 16, color: AppColors.textSecondary),
                      onPressed: onRemove,
                    ),
                  ],
                ),
                if (signalType != null) ...[
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                        decoration: BoxDecoration(color: signalColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
                        child: Text(signalType, style: TextStyle(color: signalColor, fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                      if (confidence != null) ...[
                        const SizedBox(width: 10),
                        Expanded(
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: LinearProgressIndicator(
                              value: (confidence / 100).clamp(0, 1),
                              minHeight: 6,
                              backgroundColor: AppColors.border,
                              valueColor: AlwaysStoppedAnimation(signalColor),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text('${confidence.toStringAsFixed(0)}%', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                      ],
                    ],
                  ),
                ],
                const SizedBox(height: 10),
                const Divider(height: 1),
                const SizedBox(height: 10),
                Row(
                  children: [
                    _stat('High', _fmt(data['high'])),
                    _stat('Low', _fmt(data['low'])),
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

  Widget _stat(String label, String value) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
        ],
      ),
    );
  }
}

class _AddSymbolSheet extends StatefulWidget {
  const _AddSymbolSheet();

  @override
  State<_AddSymbolSheet> createState() => _AddSymbolSheetState();
}

class _AddSymbolSheetState extends State<_AddSymbolSheet> {
  final _api = ApiClient.instance;
  final _ctrl = TextEditingController();
  Timer? _debounce;
  List<dynamic> _results = [];
  bool _adding = false;

  @override
  void dispose() {
    _ctrl.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onChanged(String v) {
    _debounce?.cancel();
    if (v.trim().isEmpty) {
      setState(() => _results = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 300), () async {
      try {
        final res = await _api.get('/portfolio/stocks/search', {'q': v.trim()});
        if (mounted) setState(() => _results = res ?? []);
      } catch (_) {}
    });
  }

  Future<void> _add(Map stock) async {
    setState(() => _adding = true);
    try {
      await _api.post('/watchlist', {'stock_id': stock['id']});
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: 16 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Add Symbol', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 12),
          TextField(
            controller: _ctrl,
            autofocus: true,
            textCapitalization: TextCapitalization.characters,
            onChanged: _onChanged,
            decoration: const InputDecoration(hintText: 'Search symbol or company name', prefixIcon: Icon(Icons.search)),
          ),
          const SizedBox(height: 12),
          ConstrainedBox(
            constraints: const BoxConstraints(maxHeight: 280),
            child: _adding
                ? const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator()))
                : ListView.builder(
                    shrinkWrap: true,
                    itemCount: _results.length,
                    itemBuilder: (context, i) {
                      final s = _results[i];
                      return ListTile(
                        title: Text(s['symbol'], style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Text(s['name'], maxLines: 1, overflow: TextOverflow.ellipsis),
                        onTap: () => _add(s),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
