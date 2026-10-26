<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions grouped by module
        $permissions = [
            // Authentification & Profil
            'auth_register_login',
            'auth_activate_2fa',
            'auth_manage_profile',
            'auth_view_other_logs',
            'auth_revoke_sessions',
            'auth_suspend_user',
            'auth_suspend_user_community', // moderator-specific restriction

            // Tableau de bord
            'dashboard_view_personal',
            'dashboard_view_global_stats',
            'dashboard_view_own_stats',

            // Actualités Décryptées
            'articles_read_published',
            'articles_write_draft',
            'articles_publish',
            'articles_publish_own',
            'articles_set_reliability',
            'articles_comment_like_share',
            'articles_share_only',
            'articles_moderate_comments',

            // Plateforme LMS
            'lms_view_catalog',
            'lms_view_preview',
            'lms_manage_course',
            'lms_manage_own_course',
            'lms_enroll_course',
            'lms_take_quiz',
            'lms_grade_manually',
            'lms_grade_own_manually',
            'lms_download_certificate',
            'lms_download_own_certificate',
            'lms_issue_certificate',

            // Fact Checking
            'fact_checking_submit',
            'fact_checking_submit_quota',
            'fact_checking_run_ai',
            'fact_checking_run_ai_quota',
            'fact_checking_validate_ai',
            'fact_checking_publish_verdict',
            'fact_checking_view_history',

            // Communauté
            'community_read',
            'community_write',
            'community_write_blog',
            'community_report',
            'community_handle_report',
            'community_lock_pin',
            'community_pm',

            // Web Radio
            'radio_listen',
            'radio_publish',
            'radio_publish_mandated',
            'radio_manage_archives',

            // Bibliothèque
            'library_download',
            'library_download_public',
            'library_upload',
            'library_validate',

            // Administration
            'admin_manage_users',
            'admin_manage_roles',
            'admin_manage_roles_assign',
            'admin_view_audit_logs',
            'admin_view_app_logs',
            'admin_configure_system',
            'admin_backups',
            'admin_global_stats_export',
        ];

        // Create permissions in DB
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Define functional roles and associate permissions

        // 1. Super Administrateur (SA) - total control
        $saRole = Role::findOrCreate('Super Administrateur', 'web');
        // SA gets all permissions implicitly via Gate::before in AppServiceProvider, but let's give them explicitly for clarity
        $saRole->syncPermissions(Permission::all());

        // 2. Administrateur (Ad)
        $adRole = Role::findOrCreate('Administrateur', 'web');
        $adRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile', 'auth_view_other_logs', 'auth_revoke_sessions', 'auth_suspend_user',
            'dashboard_view_personal', 'dashboard_view_global_stats',
            'articles_read_published', 'articles_write_draft', 'articles_publish', 'articles_set_reliability', 'articles_comment_like_share', 'articles_moderate_comments',
            'lms_view_catalog', 'lms_manage_course', 'lms_enroll_course', 'lms_grade_manually', 'lms_download_certificate',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_validate_ai', 'fact_checking_publish_verdict', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_write_blog', 'community_report', 'community_handle_report', 'community_lock_pin', 'community_pm',
            'radio_listen', 'radio_publish', 'radio_manage_archives',
            'library_download', 'library_upload', 'library_validate',
            'admin_manage_users', 'admin_manage_roles_assign', 'admin_view_app_logs', 'admin_global_stats_export',
        ]);

        // 3. Rédacteur (Ré)
        $reRole = Role::findOrCreate('Rédacteur', 'web');
        $reRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal',
            'articles_read_published', 'articles_write_draft', 'articles_publish_own', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_enroll_course',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_write_blog', 'community_report', 'community_pm',
            'radio_listen', 'radio_publish_mandated',
            'library_download', 'library_upload',
        ]);

        // 4. Fact Checker (FC)
        $fcRole = Role::findOrCreate('Fact Checker', 'web');
        $fcRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal',
            'articles_read_published', 'articles_write_draft', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_enroll_course',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_validate_ai', 'fact_checking_publish_verdict', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_write_blog', 'community_report', 'community_pm',
            'radio_listen',
            'library_download', 'library_upload',
        ]);

        // 5. Formateur (Fo)
        $foRole = Role::findOrCreate('Formateur', 'web');
        $foRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal', 'dashboard_view_own_stats',
            'articles_read_published', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_manage_own_course', 'lms_enroll_course', 'lms_grade_own_manually', 'lms_issue_certificate',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_write_blog', 'community_report', 'community_pm',
            'radio_listen',
            'library_download', 'library_upload',
        ]);

        // 6. Modérateur (Mo)
        $moRole = Role::findOrCreate('Modérateur', 'web');
        $moRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile', 'auth_suspend_user_community',
            'dashboard_view_personal',
            'articles_read_published', 'articles_comment_like_share', 'articles_moderate_comments',
            'lms_view_catalog', 'lms_enroll_course',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_report', 'community_handle_report', 'community_lock_pin', 'community_pm',
            'radio_listen',
            'library_download',
        ]);

        // 7. Partenaire (Pa)
        $paRole = Role::findOrCreate('Partenaire', 'web');
        $paRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal', 'dashboard_view_own_stats',
            'articles_read_published', 'articles_write_draft', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_manage_own_course', 'lms_enroll_course',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_write_blog', 'community_report', 'community_pm',
            'radio_listen', 'radio_publish_mandated',
            'library_download', 'library_upload',
        ]);

        // 8. Apprenant (Ap)
        $apRole = Role::findOrCreate('Apprenant', 'web');
        $apRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal',
            'articles_read_published', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_enroll_course', 'lms_take_quiz', 'lms_download_own_certificate',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_report', 'community_pm',
            'radio_listen',
            'library_download',
        ]);

        // 9. Citoyen (Ci)
        $ciRole = Role::findOrCreate('Citoyen', 'web');
        $ciRole->syncPermissions([
            'auth_register_login', 'auth_activate_2fa', 'auth_manage_profile',
            'dashboard_view_personal',
            'articles_read_published', 'articles_comment_like_share',
            'lms_view_catalog', 'lms_enroll_course', 'lms_take_quiz', 'lms_download_own_certificate',
            'fact_checking_submit', 'fact_checking_run_ai', 'fact_checking_view_history',
            'community_read', 'community_write', 'community_report', 'community_pm',
            'radio_listen',
            'library_download',
        ]);

        // 10. Invité (In) - Virtual role for non-authenticated (implemented in Policy / middleware)
        $inRole = Role::findOrCreate('Invité', 'web');
        $inRole->syncPermissions([
            'articles_read_published', 'articles_share_only',
            'lms_view_preview',
            'fact_checking_submit_quota', 'fact_checking_run_ai_quota',
            'community_read',
            'radio_listen',
            'library_download_public',
        ]);
    }
}
