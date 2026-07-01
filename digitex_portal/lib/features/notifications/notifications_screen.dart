import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/services/session_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key, required this.session});

  final SessionService session;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      _items = await widget.session.fetchNotifications();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(
            onPressed: () async {
              await widget.session.markAllNotificationsRead();
              await _load();
            },
            child: const Text('Mark all read'),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _items.isEmpty
              ? const Center(child: Text('No notifications'))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    itemCount: _items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) {
                      final n = _items[i];
                      final unread = n['is_unread'] == true;
                      return Card(
                        child: ListTile(
                          leading: CircleAvatar(
                            backgroundColor: AppTheme.primary.withValues(alpha: 0.1),
                            child: const Icon(Icons.notifications, color: AppTheme.primary, size: 20),
                          ),
                          title: Text(n['title'] as String? ?? '', style: TextStyle(fontWeight: unread ? FontWeight.bold : FontWeight.normal)),
                          subtitle: Text(n['message'] as String? ?? ''),
                          trailing: Text(n['time_ago'] as String? ?? '', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
