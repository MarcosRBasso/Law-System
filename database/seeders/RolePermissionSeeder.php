<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = $this->getPermissions();
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'description' => $permission['description'],
                    'guard_name' => 'web'
                ]
            );
        }

        // Create roles and assign permissions
        $this->createSuperAdminRole();
        $this->createAdminRole();
        $this->createAdvogadoRole();
        $this->createSecretarioRole();
        $this->createFinanceiroRole();
        $this->createClienteRole();
        $this->createEstagiarioRole();
    }

    /**
     * Get all permissions for the system
     */
    protected function getPermissions(): array
    {
        return [
            // Users module
            ['name' => 'users.view', 'description' => 'Visualizar usuários'],
            ['name' => 'users.create', 'description' => 'Criar usuários'],
            ['name' => 'users.update', 'description' => 'Editar usuários'],
            ['name' => 'users.delete', 'description' => 'Excluir usuários'],
            ['name' => 'users.assign-roles', 'description' => 'Atribuir perfis a usuários'],

            // Roles module
            ['name' => 'roles.view', 'description' => 'Visualizar perfis'],
            ['name' => 'roles.create', 'description' => 'Criar perfis'],
            ['name' => 'roles.update', 'description' => 'Editar perfis'],
            ['name' => 'roles.delete', 'description' => 'Excluir perfis'],

            // Clients module
            ['name' => 'clients.view', 'description' => 'Visualizar clientes'],
            ['name' => 'clients.create', 'description' => 'Criar clientes'],
            ['name' => 'clients.update', 'description' => 'Editar clientes'],
            ['name' => 'clients.delete', 'description' => 'Excluir clientes'],
            ['name' => 'clients.import', 'description' => 'Importar clientes'],
            ['name' => 'clients.export', 'description' => 'Exportar clientes'],

            // Lawsuits module
            ['name' => 'lawsuits.view', 'description' => 'Visualizar processos'],
            ['name' => 'lawsuits.create', 'description' => 'Criar processos'],
            ['name' => 'lawsuits.update', 'description' => 'Editar processos'],
            ['name' => 'lawsuits.delete', 'description' => 'Excluir processos'],
            ['name' => 'lawsuits.sync', 'description' => 'Sincronizar movimentações'],
            ['name' => 'lawsuits.view-all', 'description' => 'Visualizar todos os processos'],

            // Documents module
            ['name' => 'documents.view', 'description' => 'Visualizar documentos'],
            ['name' => 'documents.create', 'description' => 'Criar documentos'],
            ['name' => 'documents.update', 'description' => 'Editar documentos'],
            ['name' => 'documents.delete', 'description' => 'Excluir documentos'],
            ['name' => 'documents.download', 'description' => 'Baixar documentos'],
            ['name' => 'documents.sign', 'description' => 'Assinar documentos'],
            ['name' => 'documents.templates', 'description' => 'Gerenciar templates'],

            // Time entries module
            ['name' => 'time-entries.view', 'description' => 'Visualizar lançamentos de tempo'],
            ['name' => 'time-entries.create', 'description' => 'Criar lançamentos de tempo'],
            ['name' => 'time-entries.update', 'description' => 'Editar lançamentos de tempo'],
            ['name' => 'time-entries.delete', 'description' => 'Excluir lançamentos de tempo'],
            ['name' => 'time-entries.view-all', 'description' => 'Visualizar todos os lançamentos'],

            // Invoices module
            ['name' => 'invoices.view', 'description' => 'Visualizar faturas'],
            ['name' => 'invoices.create', 'description' => 'Criar faturas'],
            ['name' => 'invoices.update', 'description' => 'Editar faturas'],
            ['name' => 'invoices.delete', 'description' => 'Excluir faturas'],
            ['name' => 'invoices.send', 'description' => 'Enviar faturas'],
            ['name' => 'invoices.view-all', 'description' => 'Visualizar todas as faturas'],

            // Financial module
            ['name' => 'financial.view', 'description' => 'Visualizar financeiro'],
            ['name' => 'financial.create', 'description' => 'Criar transações'],
            ['name' => 'financial.update', 'description' => 'Editar transações'],
            ['name' => 'financial.delete', 'description' => 'Excluir transações'],
            ['name' => 'financial.reconcile', 'description' => 'Conciliar contas'],
            ['name' => 'financial.reports', 'description' => 'Relatórios financeiros'],

            // Calendar module
            ['name' => 'calendar.view', 'description' => 'Visualizar agenda'],
            ['name' => 'calendar.create', 'description' => 'Criar eventos'],
            ['name' => 'calendar.update', 'description' => 'Editar eventos'],
            ['name' => 'calendar.delete', 'description' => 'Excluir eventos'],
            ['name' => 'calendar.view-all', 'description' => 'Visualizar agenda de todos'],

            // Deadlines module
            ['name' => 'deadlines.view', 'description' => 'Visualizar prazos'],
            ['name' => 'deadlines.create', 'description' => 'Criar prazos'],
            ['name' => 'deadlines.update', 'description' => 'Editar prazos'],
            ['name' => 'deadlines.delete', 'description' => 'Excluir prazos'],
            ['name' => 'deadlines.view-all', 'description' => 'Visualizar todos os prazos'],

            // Reports module
            ['name' => 'reports.view', 'description' => 'Visualizar relatórios'],
            ['name' => 'reports.export', 'description' => 'Exportar relatórios'],
            ['name' => 'reports.financial', 'description' => 'Relatórios financeiros'],
            ['name' => 'reports.productivity', 'description' => 'Relatórios de produtividade'],

            // Admin module
            ['name' => 'admin.settings', 'description' => 'Configurações do sistema'],
            ['name' => 'admin.backup', 'description' => 'Backup e restauração'],
            ['name' => 'admin.logs', 'description' => 'Logs do sistema'],
            ['name' => 'admin.maintenance', 'description' => 'Modo manutenção'],

            // System module
            ['name' => 'system.health', 'description' => 'Status do sistema'],
            ['name' => 'system.monitor', 'description' => 'Monitoramento'],
            ['name' => 'system.integrations', 'description' => 'Integrações externas'],
        ];
    }

    /**
     * Create Super Admin role with all permissions
     */
    protected function createSuperAdminRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['description' => 'Super Administrador - Acesso total ao sistema']
        );

        // Give all permissions to super admin
        $role->givePermissionTo(Permission::all());
    }

    /**
     * Create Admin role
     */
    protected function createAdminRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador - Gerencia usuários e configurações']
        );

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-roles',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'clients.view', 'clients.create', 'clients.update', 'clients.delete', 'clients.import', 'clients.export',
            'lawsuits.view', 'lawsuits.create', 'lawsuits.update', 'lawsuits.delete', 'lawsuits.sync', 'lawsuits.view-all',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download', 'documents.templates',
            'time-entries.view', 'time-entries.view-all',
            'invoices.view', 'invoices.view-all',
            'financial.view', 'financial.reports',
            'calendar.view', 'calendar.view-all',
            'deadlines.view', 'deadlines.view-all',
            'reports.view', 'reports.export', 'reports.financial', 'reports.productivity',
            'admin.settings', 'admin.backup', 'admin.logs',
            'system.health', 'system.monitor', 'system.integrations'
        ];

        $role->givePermissionTo($permissions);
    }

    /**
     * Create Advogado (Lawyer) role
     */
    protected function createAdvogadoRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'advogado'],
            ['description' => 'Advogado - Gerencia processos, clientes e documentos']
        );

        $permissions = [
            'clients.view', 'clients.create', 'clients.update', 'clients.export',
            'lawsuits.view', 'lawsuits.create', 'lawsuits.update', 'lawsuits.sync',
            'documents.view', 'documents.create', 'documents.update', 'documents.download', 'documents.sign',
            'time-entries.view', 'time-entries.create', 'time-entries.update', 'time-entries.delete',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send',
            'financial.view',
            'calendar.view', 'calendar.create', 'calendar.update', 'calendar.delete',
            'deadlines.view', 'deadlines.create', 'deadlines.update', 'deadlines.delete',
            'reports.view', 'reports.export', 'reports.productivity'
        ];

        $role->givePermissionTo($permissions);
    }

    /**
     * Create Secretario (Secretary) role
     */
    protected function createSecretarioRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'secretario'],
            ['description' => 'Secretário - Suporte administrativo e agendamento']
        );

        $permissions = [
            'clients.view', 'clients.create', 'clients.update', 'clients.import',
            'lawsuits.view', 'lawsuits.create', 'lawsuits.update',
            'documents.view', 'documents.create', 'documents.update', 'documents.download',
            'calendar.view', 'calendar.create', 'calendar.update', 'calendar.delete', 'calendar.view-all',
            'deadlines.view', 'deadlines.create', 'deadlines.update', 'deadlines.view-all',
            'reports.view'
        ];

        $role->givePermissionTo($permissions);
    }

    /**
     * Create Financeiro (Financial) role
     */
    protected function createFinanceiroRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'financeiro'],
            ['description' => 'Financeiro - Gerencia transações e relatórios financeiros']
        );

        $permissions = [
            'clients.view',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send', 'invoices.view-all',
            'financial.view', 'financial.create', 'financial.update', 'financial.delete', 'financial.reconcile', 'financial.reports',
            'reports.view', 'reports.export', 'reports.financial'
        ];

        $role->givePermissionTo($permissions);
    }

    /**
     * Create Cliente (Client) role
     */
    protected function createClienteRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'cliente'],
            ['description' => 'Cliente - Acesso limitado aos próprios processos']
        );

        $permissions = [
            'lawsuits.view',
            'documents.view', 'documents.download',
            'invoices.view',
            'calendar.view'
        ];

        $role->givePermissionTo($permissions);
    }

    /**
     * Create Estagiario (Intern) role
     */
    protected function createEstagiarioRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'estagiario'],
            ['description' => 'Estagiário - Acesso limitado para aprendizado']
        );

        $permissions = [
            'clients.view',
            'lawsuits.view',
            'documents.view', 'documents.download',
            'time-entries.view', 'time-entries.create', 'time-entries.update',
            'calendar.view',
            'deadlines.view'
        ];

        $role->givePermissionTo($permissions);
    }
}