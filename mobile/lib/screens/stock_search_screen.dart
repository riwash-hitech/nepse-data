import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';
import 'stock_detail_screen.dart';

/// Full-screen search popup — opened from the search icon on the dashboard.
/// Mirrors the web app's Markets page: search by symbol/company name, filter
/// by sector, and browse the complete (paginated) stock list.
class StockSearchScreen extends StatefulWidget {
  const StockSearchScreen({super.key});

  @override
  State<StockSearchScreen> createState() => _StockSearchScreenState();
}

class _StockSearchScreenState extends State<StockSearchScreen> {
  final _api = ApiClient.instance;
  final _searchCtrl = TextEditingController();
  final _focusNode = FocusNode();
  Timer? _debounce;

  List<dynamic> _stocks = [];
  List<dynamic> _sectors = [];
  String _sectorId = '';
  bool _loading = true;
  String? _error;
  int _page = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _load();
    WidgetsBinding.instance.addPostFrameCallback((_) => _focusNode.requestFocus());
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _focusNode.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load({int page = 1}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.get('/markets', {
        'search': _searchCtrl.text.trim(),
        'sector': _sectorId,
        'page': page,
      });
      setState(() {
        _stocks = res['data'] ?? [];
        _sectors = res['sectors'] ?? [];
        _total = res['total'] ?? 0;
        _page = page;
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
    _debounce = Timer(const Duration(milliseconds: 400), () => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Search Stocks'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => _load(page: _page), tooltip: 'Refresh live data'),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              controller: _searchCtrl,
              focusNode: _focusNode,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search symbol or company name',
                prefixIcon: const Icon(Icons.search, color: AppColors.textSecondary),
                suffixIcon: _searchCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.close, size: 18),
                        onPressed: () {
                          _searchCtrl.clear();
                          _load();
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 12),
              ),
            ),
          ),
          if (_sectors.isNotEmpty)
            SizedBox(
              height: 36,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  _sectorChip('All', ''),
                  ..._sectors.map((s) => _sectorChip(s['name'] ?? '', '${s['id']}')),
                ],
              ),
            ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text('$_total stocks found', style: const TextStyle(color: AppColors.textSecondary, fontSize: 12)),
            ),
          ),
          const SizedBox(height: 4),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.textSecondary)))
                    : _stocks.isEmpty
                        ? const Center(child: Text('No stocks match your search.', style: TextStyle(color: AppColors.textSecondary)))
                        : RefreshIndicator(
                            onRefresh: () => _load(page: _page),
                            child: ListView.separated(
                              padding: const EdgeInsets.only(bottom: 16),
                              itemCount: _stocks.length,
                              separatorBuilder: (_, i) => const Divider(height: 1),
                              itemBuilder: (context, i) {
                                final s = _stocks[i];
                                return ListTile(
                                  title: Text(s['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Text(s['name'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
                                  trailing: const Icon(Icons.chevron_right, color: AppColors.textSecondary),
                                  onTap: () => Navigator.of(context).push(
                                    MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: s['symbol'])),
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

  Widget _sectorChip(String label, String value) {
    final selected = _sectorId == value;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label, style: const TextStyle(fontSize: 12)),
        selected: selected,
        onSelected: (_) {
          setState(() => _sectorId = value);
          _load();
        },
        selectedColor: AppColors.brand,
        backgroundColor: AppColors.surface,
        labelStyle: TextStyle(color: selected ? Colors.white : AppColors.textSecondary),
        side: const BorderSide(color: AppColors.border),
      ),
    );
  }
}
