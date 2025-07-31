<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $nameParts = explode('.', $this->name);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->getDisplayName(),
            'description' => $this->description,
            'module' => $nameParts[0] ?? 'general',
            'action' => $nameParts[1] ?? $this->name,
            'guard_name' => $this->guard_name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get a human-readable display name for the permission
     */
    protected function getDisplayName(): string
    {
        $displayNames = [
            // Users module
            'users.view' => 'Visualizar usuários',
            'users.create' => 'Criar usuários',
            'users.update' => 'Editar usuários',
            'users.delete' => 'Excluir usuários',
            'users.assign-roles' => 'Atribuir perfis',
            
            // Roles module
            'roles.view' => 'Visualizar perfis',
            'roles.create' => 'Criar perfis',
            'roles.update' => 'Editar perfis',
            'roles.delete' => 'Excluir perfis',
            
            // Clients module
            'clients.view' => 'Visualizar clientes',
            'clients.create' => 'Criar clientes',
            'clients.update' => 'Editar clientes',
            'clients.delete' => 'Excluir clientes',
            'clients.import' => 'Importar clientes',
            'clients.export' => 'Exportar clientes',
            
            // Lawsuits module
            'lawsuits.view' => 'Visualizar processos',
            'lawsuits.create' => 'Criar processos',
            'lawsuits.update' => 'Editar processos',
            'lawsuits.delete' => 'Excluir processos',
            'lawsuits.sync' => 'Sincronizar movimentações',
            
            // Documents module
            'documents.view' => 'Visualizar documentos',
            'documents.create' => 'Criar documentos',
            'documents.update' => 'Editar documentos',
            'documents.delete' => 'Excluir documentos',
            'documents.download' => 'Baixar documentos',
            'documents.sign' => 'Assinar documentos',
            
            // Time entries module
            'time-entries.view' => 'Visualizar lançamentos de tempo',
            'time-entries.create' => 'Criar lançamentos de tempo',
            'time-entries.update' => 'Editar lançamentos de tempo',
            'time-entries.delete' => 'Excluir lançamentos de tempo',
            
            // Invoices module
            'invoices.view' => 'Visualizar faturas',
            'invoices.create' => 'Criar faturas',
            'invoices.update' => 'Editar faturas',
            'invoices.delete' => 'Excluir faturas',
            'invoices.send' => 'Enviar faturas',
            
            // Financial module
            'financial.view' => 'Visualizar financeiro',
            'financial.create' => 'Criar transações',
            'financial.update' => 'Editar transações',
            'financial.delete' => 'Excluir transações',
            'financial.reconcile' => 'Conciliar contas',
            'financial.reports' => 'Relatórios financeiros',
            
            // Calendar module
            'calendar.view' => 'Visualizar agenda',
            'calendar.create' => 'Criar eventos',
            'calendar.update' => 'Editar eventos',
            'calendar.delete' => 'Excluir eventos',
            
            // Deadlines module
            'deadlines.view' => 'Visualizar prazos',
            'deadlines.create' => 'Criar prazos',
            'deadlines.update' => 'Editar prazos',
            'deadlines.delete' => 'Excluir prazos',
            
            // Reports module
            'reports.view' => 'Visualizar relatórios',
            'reports.export' => 'Exportar relatórios',
            'reports.financial' => 'Relatórios financeiros',
            'reports.productivity' => 'Relatórios de produtividade',
            
            // Admin module
            'admin.settings' => 'Configurações do sistema',
            'admin.backup' => 'Backup e restauração',
            'admin.logs' => 'Logs do sistema',
            'admin.maintenance' => 'Modo manutenção',
            
            // System module
            'system.health' => 'Status do sistema',
            'system.monitor' => 'Monitoramento',
            'system.integrations' => 'Integrações externas'
        ];

        return $displayNames[$this->name] ?? $this->name;
    }
}