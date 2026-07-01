import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/services/session_service.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key, required this.session});

  final SessionService session;

  @override
  Widget build(BuildContext context) {
    final ctx = session.context!;
    return Scaffold(
      appBar: AppBar(title: const Text('My Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          CircleAvatar(
            radius: 40,
            backgroundColor: AppTheme.primary.withValues(alpha: 0.12),
            child: Text(ctx.name.isNotEmpty ? ctx.name[0].toUpperCase() : '?',
                style: const TextStyle(fontSize: 28, color: AppTheme.primary, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 12),
          Text(ctx.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          Text(ctx.email, style: const TextStyle(color: AppTheme.textMuted)),
          const SizedBox(height: 20),
          _infoTile('Role', ctx.activeRole),
          _infoTile('School', ctx.schoolName),
          if (ctx.planName != null) _infoTile('Plan', ctx.planName!),
          if (ctx.sessionName != null) _infoTile('Session', ctx.sessionName!),
        ],
      ),
    );
  }

  Widget _infoTile(String label, String value) => Card(
        child: ListTile(title: Text(label, style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)), subtitle: Text(value)),
      );
}
