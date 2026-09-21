-- Licensing plugin — uninstall. Drops every licensing_* table.
-- Dropped in child-before-parent order (checkins/installs reference plans/
-- products/clients only by plain INT column, no FK, but this order keeps
-- the file readable as a mirror of the dependency direction).
DROP TABLE IF EXISTS `licensing_checkins`;
DROP TABLE IF EXISTS `licensing_installs`;
DROP TABLE IF EXISTS `licensing_plans`;
DROP TABLE IF EXISTS `licensing_clients`;
DROP TABLE IF EXISTS `licensing_products`;
