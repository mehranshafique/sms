import 'package:flutter/material.dart';
import '../../../config/theme.dart';
import '../../../core/models/mobile_context.dart';

class MenuSection extends StatelessWidget {
  const MenuSection({
    super.key,
    required this.title,
    required this.tiles,
    required this.onTileTap,
    this.crossAxisCount = 2,
  });

  final String title;
  final List<MenuTile> tiles;
  final void Function(MenuTile tile) onTileTap;
  final int crossAxisCount;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppTheme.textPrimary)),
        const SizedBox(height: 12),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: crossAxisCount == 2 ? 1.05 : 1.2,
          ),
          itemCount: tiles.length,
          itemBuilder: (_, i) => _MenuTileCard(tile: tiles[i], onTap: () => onTileTap(tiles[i])),
        ),
      ],
    );
  }
}

class _MenuTileCard extends StatelessWidget {
  const _MenuTileCard({required this.tile, required this.onTap});

  final MenuTile tile;
  final VoidCallback onTap;

  IconData _iconFor(String name) {
    const map = {
      'nfc': Icons.nfc,
      'badge': Icons.badge_outlined,
      'qr_code_scanner': Icons.qr_code_scanner,
      'payments': Icons.payments_outlined,
      'search': Icons.search,
      'description': Icons.description_outlined,
      'verified_user': Icons.verified_user_outlined,
      'fact_check': Icons.fact_check_outlined,
      'person_off': Icons.person_off_outlined,
      'schedule': Icons.schedule,
      'list_alt': Icons.list_alt,
      'campaign': Icons.campaign_outlined,
      'support_agent': Icons.support_agent,
      'qr_code': Icons.qr_code,
      'event_available': Icons.event_available,
      'receipt_long': Icons.receipt_long,
      'school': Icons.school_outlined,
      'assignment': Icons.assignment_outlined,
      'mail': Icons.mail_outline,
      'family_restroom': Icons.family_restroom,
    };
    return map[name] ?? Icons.apps;
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppTheme.card,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(_iconFor(tile.icon), color: AppTheme.primary, size: 22),
              ),
              const Spacer(),
              Text(tile.title, maxLines: 2, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: AppTheme.textPrimary)),
              const SizedBox(height: 2),
              Text(tile.subtitle, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
            ],
          ),
        ),
      ),
    );
  }
}
