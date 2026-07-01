class MenuTile {
  MenuTile({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.route,
    required this.enabled,
  });

  final String id;
  final String title;
  final String subtitle;
  final String icon;
  final String route;
  final bool enabled;

  factory MenuTile.fromJson(Map<String, dynamic> json) => MenuTile(
        id: json['id'] as String? ?? '',
        title: json['title'] as String? ?? '',
        subtitle: json['subtitle'] as String? ?? '',
        icon: json['icon'] as String? ?? 'apps',
        route: json['route'] as String? ?? '/',
        enabled: json['enabled'] as bool? ?? false,
      );
}

class MobileMenu {
  MobileMenu({
    required this.layout,
    required this.gateTerminal,
    required this.staffTools,
    required this.studentPortal,
  });

  final String layout;
  final List<MenuTile> gateTerminal;
  final List<MenuTile> staffTools;
  final List<MenuTile> studentPortal;

  factory MobileMenu.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return MobileMenu(layout: 'standard', gateTerminal: [], staffTools: [], studentPortal: []);
    }
    List<MenuTile> parseList(dynamic raw) =>
        (raw as List<dynamic>? ?? []).map((e) => MenuTile.fromJson(e as Map<String, dynamic>)).toList();

    return MobileMenu(
      layout: json['layout'] as String? ?? 'standard',
      gateTerminal: parseList(json['gate_terminal']),
      staffTools: parseList(json['staff_tools']),
      studentPortal: parseList(json['student_portal']),
    );
  }
}

class MobileContext {
  MobileContext({
    required this.userId,
    required this.name,
    required this.email,
    required this.activeRole,
    required this.switchableRoles,
    required this.schoolName,
    required this.schoolLogo,
    required this.sessionName,
    required this.capabilities,
    required this.menu,
    required this.subscription,
    required this.children,
    required this.unreadNotifications,
  });

  final int userId;
  final String name;
  final String email;
  final String activeRole;
  final List<String> switchableRoles;
  final String schoolName;
  final String? schoolLogo;
  final String? sessionName;
  final Map<String, bool> capabilities;
  final MobileMenu menu;
  final Map<String, dynamic> subscription;
  final List<Map<String, dynamic>> children;
  int unreadNotifications;

  bool get isGateMode => capabilities['gate_mode'] == true;
  bool get hasMultiRole => switchableRoles.length > 1;
  String? get planName => subscription['plan_name'] as String?;
  bool get subscriptionActive => subscription['active'] == true;

  factory MobileContext.fromLogin(Map<String, dynamic> user, {int unread = 0}) {
    final caps = (user['capabilities'] as Map<String, dynamic>? ?? {})
        .map((k, v) => MapEntry(k, v == true));

    return MobileContext(
      userId: user['id'] as int? ?? 0,
      name: user['name'] as String? ?? '',
      email: user['email'] as String? ?? '',
      activeRole: user['active_role'] as String? ?? user['role'] as String? ?? '',
      switchableRoles: (user['switchable_roles'] as List<dynamic>? ?? []).cast<String>(),
      schoolName: user['school_name'] as String? ?? 'Digitex',
      schoolLogo: user['school_logo'] as String?,
      sessionName: user['academic_session_name'] as String?,
      capabilities: caps,
      menu: MobileMenu.fromJson(user['menu'] as Map<String, dynamic>?),
      subscription: user['subscription'] as Map<String, dynamic>? ?? {},
      children: (user['children'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>(),
      unreadNotifications: unread,
    );
  }

  factory MobileContext.fromContextPayload(Map<String, dynamic> data, MobileContext previous) {
    final merged = {
      'id': data['user_id'],
      'name': previous.name,
      'email': previous.email,
      'active_role': data['active_role'],
      'role': data['active_role'],
      'switchable_roles': data['switchable_roles'],
      'school_name': data['school_name'],
      'school_logo': data['school_logo'],
      'academic_session_name': data['academic_session_name'],
      'capabilities': data['capabilities'],
      'menu': data['menu'],
      'subscription': data['subscription'],
      'children': data['children'],
    };
    return MobileContext.fromLogin(merged, unread: previous.unreadNotifications);
  }
}
