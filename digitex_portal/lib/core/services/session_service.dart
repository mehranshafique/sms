import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../models/mobile_context.dart';

class SessionService {
  SessionService(this._api);

  final ApiClient _api;
  static const _tokenKey = 'auth_token';

  MobileContext? context;
  Timer? _notifTimer;

  Future<bool> restore() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_tokenKey);
    if (token == null) return false;
    _api.setToken(token);
    await refreshContext();
    _startNotificationPolling();
    return true;
  }

  Future<void> login(String email, String password) async {
    final res = await _api.post('/login', {'email': email, 'password': password});
    final token = res['token'] as String?;
    if (token == null) throw ApiException(401, 'No token returned');
    _api.setToken(token);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    context = MobileContext.fromLogin(res['user'] as Map<String, dynamic>);
    await _fetchUnreadCount();
    _startNotificationPolling();
  }

  Future<void> logout() async {
    _notifTimer?.cancel();
    try {
      await _api.post('/logout');
    } catch (_) {}
    _api.setToken(null);
    context = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<void> refreshContext() async {
    final res = await _api.get('/me/context');
    final data = res['data'] as Map<String, dynamic>;
    if (context != null) {
      context = MobileContext.fromContextPayload(data, context!);
    } else {
      context = MobileContext.fromLogin({
        'id': data['user_id'],
        'name': '',
        'email': '',
        ...data,
      });
    }
    await _fetchUnreadCount();
  }

  Future<void> switchRole(String role) async {
    final res = await _api.post('/me/switch-role', {'role': role});
    final data = res['data'] as Map<String, dynamic>;
    context = MobileContext.fromContextPayload(data, context!);
  }

  Future<List<Map<String, dynamic>>> fetchNotifications() async {
    final res = await _api.get('/notifications/feed');
    final data = res['data'] as Map<String, dynamic>? ?? {};
    context?.unreadNotifications = data['unread_count'] as int? ?? 0;
    return (data['notifications'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>();
  }

  Future<void> markAllNotificationsRead() async {
    await _api.post('/notifications/read-all');
    context?.unreadNotifications = 0;
  }

  void _startNotificationPolling() {
    _notifTimer?.cancel();
    _notifTimer = Timer.periodic(const Duration(seconds: 30), (_) => _fetchUnreadCount());
  }

  Future<void> _fetchUnreadCount() async {
    try {
      final res = await _api.get('/notifications/feed', {'limit': '1'});
      final data = res['data'] as Map<String, dynamic>? ?? {};
      context?.unreadNotifications = data['unread_count'] as int? ?? 0;
    } catch (_) {}
  }
}
