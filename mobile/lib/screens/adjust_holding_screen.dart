import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';

class AdjustHoldingScreen extends StatefulWidget {
  const AdjustHoldingScreen({super.key});

  @override
  State<AdjustHoldingScreen> createState() => _AdjustHoldingScreenState();
}

class _AdjustHoldingScreenState extends State<AdjustHoldingScreen> {
  final _api = ApiClient.instance;
  final _symbolCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController();
  final _rateCtrl = TextEditingController();
  final _remarksCtrl = TextEditingController();

  Timer? _debounce;
  List<dynamic> _suggestions = [];
  Map<String, dynamic>? _selectedStock;
  String _type = 'buy';
  DateTime _date = DateTime.now();
  bool _submitting = false;

  @override
  void dispose() {
    _symbolCtrl.dispose();
    _qtyCtrl.dispose();
    _rateCtrl.dispose();
    _remarksCtrl.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onSymbolChanged(String v) {
    _selectedStock = null;
    _debounce?.cancel();
    if (v.trim().isEmpty) {
      setState(() => _suggestions = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 300), () async {
      try {
        final res = await _api.get('/portfolio/stocks/search', {'q': v.trim()});
        if (mounted) setState(() => _suggestions = res ?? []);
      } catch (_) {}
    });
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2015),
      lastDate: DateTime.now(),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _submit() async {
    if (_selectedStock == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Select a stock from the suggestions.')));
      return;
    }
    final qty = int.tryParse(_qtyCtrl.text);
    final rate = double.tryParse(_rateCtrl.text);
    if (qty == null || qty <= 0 || rate == null || rate < 0) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Enter a valid quantity and rate.')));
      return;
    }

    setState(() => _submitting = true);
    try {
      await _api.post('/portfolio/adjust', {
        'symbol': _selectedStock!['symbol'],
        'type': _type,
        'quantity': qty,
        'rate': rate,
        'txn_date': DateFormat('yyyy-MM-dd').format(_date),
        'remarks': _remarksCtrl.text.trim().isEmpty ? null : _remarksCtrl.text.trim(),
      });
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Buy / Sell')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(
            controller: _symbolCtrl,
            onChanged: _onSymbolChanged,
            textCapitalization: TextCapitalization.characters,
            decoration: const InputDecoration(labelText: 'Stock symbol'),
          ),
          if (_suggestions.isNotEmpty)
            Container(
              margin: const EdgeInsets.only(top: 4),
              decoration: BoxDecoration(
                color: AppColors.surface,
                border: Border.all(color: AppColors.border),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                children: _suggestions.map((s) {
                  return ListTile(
                    dense: true,
                    title: Text(s['symbol']),
                    subtitle: Text(s['name'], maxLines: 1, overflow: TextOverflow.ellipsis),
                    onTap: () {
                      setState(() {
                        _selectedStock = Map<String, dynamic>.from(s);
                        _symbolCtrl.text = s['symbol'];
                        _suggestions = [];
                      });
                    },
                  );
                }).toList(),
              ),
            ),
          const SizedBox(height: 16),
          SegmentedButton<String>(
            segments: const [
              ButtonSegment(value: 'buy', label: Text('Buy')),
              ButtonSegment(value: 'sell', label: Text('Sell')),
            ],
            selected: {_type},
            onSelectionChanged: (s) => setState(() => _type = s.first),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _qtyCtrl,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'Quantity'),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _rateCtrl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(labelText: 'Rate per share (Rs.)'),
          ),
          const SizedBox(height: 16),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Transaction date'),
            subtitle: Text(DateFormat('yyyy-MM-dd').format(_date)),
            trailing: const Icon(Icons.calendar_today, size: 18),
            onTap: _pickDate,
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _remarksCtrl,
            decoration: const InputDecoration(labelText: 'Remarks (optional)'),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _submitting ? null : _submit,
            style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
            child: _submitting
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : Text(_type == 'buy' ? 'Record Buy' : 'Record Sell'),
          ),
        ],
      ),
    );
  }
}
