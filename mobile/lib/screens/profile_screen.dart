import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_provider.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          CircleAvatar(
            radius: 36,
            backgroundColor: AppColors.accent,
            child: Text(
              (auth.name?.isNotEmpty == true ? auth.name![0] : '?').toUpperCase(),
              style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold),
            ),
          ),
          const SizedBox(height: 12),
          Center(child: Text(auth.name ?? '', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
          Center(child: Text(auth.email ?? '', style: const TextStyle(color: AppColors.textSecondary))),
          if (auth.isAdmin)
            const Center(
              child: Padding(
                padding: EdgeInsets.only(top: 6),
                child: Chip(label: Text('Admin')),
              ),
            ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Text('Server: ${ApiClient.instance.baseUrl}', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          ),
          const SizedBox(height: 24),
          OutlinedButton.icon(
            onPressed: () => auth.logout(),
            icon: const Icon(Icons.logout),
            label: const Text('Sign out'),
          ),
        ],
      ),
    );
  }
}
