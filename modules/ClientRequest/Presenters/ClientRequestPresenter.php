<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Models\ProcessStep;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestPresenter extends AbstractPresenter
{
    private ClientRequest $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->clientRequest->id,
            'serial_number' => $this->clientRequest->serial_number,
            'company_id' => $this->clientRequest->company_id,
            'client_request_type_id' => $this->clientRequest->client_request_type_id,
            'client_request_receiver_from_id' => $this->clientRequest->client_request_receiver_from_id,
            'client_type' => $this->clientRequest->client_type,
            'client_id' => $this->clientRequest->client_id,
            'content' => $this->clientRequest->content,
            'status_client_request' => $this->clientRequest->status_client_request,
            'client_price_offer_status' => $this->clientRequest->client_price_offer_status,
            'branch_id' => $this->clientRequest->branch_id,
            'management_id' => $this->clientRequest->management_id,
            'created_at' => $this->clientRequest->created_at?->toDateTimeString(),
            'updated_at' => $this->clientRequest->updated_at?->toDateTimeString(),
        ];

        // Add company relationship
        $data['company'] = null;
        if ($this->clientRequest->relationLoaded('company') && $this->clientRequest->company) {
            $data['company'] = [
                'id' => $this->clientRequest->company->id,
                'name' => $this->clientRequest->company->name ?? null,
                'email' => $this->clientRequest->company->email ?? null,
                'phone' => $this->clientRequest->company->phone ?? null,
            ];
        }

        // Add client relationship
        $data['client'] = null;
        if ($this->clientRequest->relationLoaded('client') && $this->clientRequest->client) {
            $data['client'] = [
                'id' => $this->clientRequest->client->id,
                'name' => $this->clientRequest->client->name ?? null,
                'email' => $this->clientRequest->client->email ?? null,
                'phone' => $this->clientRequest->client->phone ?? null,
            ];
        }

        // Add client request type relationship
        $data['client_request_type'] = null;
        if ($this->clientRequest->relationLoaded('clientRequestType') && $this->clientRequest->clientRequestType) {
            $data['client_request_type'] = [
                'id' => $this->clientRequest->clientRequestType->id,
                'name' => $this->clientRequest->clientRequestType->name,
                'type' => $this->clientRequest->clientRequestType->type,
                'is_active' => $this->clientRequest->clientRequestType->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestType->created_at?->toDateTimeString(),
            ];
        }

        // Add client request receiver from relationship
        $data['client_request_receiver_from'] = null;
        if ($this->clientRequest->relationLoaded('clientRequestReceiverFrom') && $this->clientRequest->clientRequestReceiverFrom) {
            $data['client_request_receiver_from'] = [
                'id' => $this->clientRequest->clientRequestReceiverFrom->id,
                'name' => $this->clientRequest->clientRequestReceiverFrom->name,
                'type' => $this->clientRequest->clientRequestReceiverFrom->type,
                'is_active' => $this->clientRequest->clientRequestReceiverFrom->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestReceiverFrom->created_at?->toDateTimeString(),
            ];
        }

        // Add services relationship
        $data['services'] = [];
        if ($this->clientRequest->relationLoaded('services')&& $this->clientRequest->services) {
            $data['services'] = $this->clientRequest->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'is_active' => $service->is_active ?? null,
                    'created_at' => $service->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        // Add term settings relationship - build tree from serviceTerms
        $data['term_service_settings'] = [];
        if ($this->clientRequest->relationLoaded('serviceTerms') && $this->clientRequest->serviceTerms) {
            $data['term_service_settings'] = $this->buildServiceTermTrees($this->clientRequest->serviceTerms);
        }
        
        // Keep old term_settings for backward compatibility (flat list)
        $data['term_settings'] = [];
        if ($this->clientRequest->relationLoaded('termSettings') && $this->clientRequest->termSettings) {
            $data['term_settings'] = $this->clientRequest->termSettings->map(function ($termSetting) {
                return [
                    'id' => $termSetting->id,
                    'name' => $termSetting->name ?? null,
                    'description' => $termSetting->description ?? null,
                    'is_active' => $termSetting->is_active ?? null,
                    'created_at' => $termSetting->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        // Add branch relationship - always include even if null
        $data['branch'] = null;
        if ($this->clientRequest->relationLoaded('branch') && $this->clientRequest->branch) {
            $data['branch'] = [
                'id' => $this->clientRequest->branch->id,
                'name' => $this->clientRequest->branch->name,
                'type' => $this->clientRequest->branch->type,
                'is_active' => $this->clientRequest->branch->is_active ?? null,
                'users_count' => $this->clientRequest->branch->users_count ?? 0,
                'created_at' => $this->clientRequest->branch->created_at?->toDateTimeString(),
            ];
        }

        // Add management relationship - always include even if null
        $data['management'] = null;
        if ($this->clientRequest->relationLoaded('management') && $this->clientRequest->management) {
            $data['management'] = [
                'id' => $this->clientRequest->management->id,
                'name' => $this->clientRequest->management->name,
                'type' => $this->clientRequest->management->type,
                'is_active' => $this->clientRequest->management->is_active ?? null,
                'users_count' => $this->clientRequest->management->users_count ?? 0,
                'created_at' => $this->clientRequest->management->created_at?->toDateTimeString(),
            ];
        }

        // Add media attachments
        $data['attachments'] = [];
        if ($this->clientRequest->relationLoaded('media')) {
            $data['attachments'] = $this->clientRequest->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_readable_size' => $this->formatBytes($media->size),
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        $data['process'] = null;
        $data['process_steps'] = [];
        $data['workflow'] = null;
        if ($this->clientRequest->relationLoaded('clientRequestProcess')) {
            $process = $this->clientRequest->clientRequestProcess;
            if ($process !== null) {
                $steps = $process->relationLoaded('steps') ? $process->steps : collect();
                $processPayload = [
                    'id'           => $process->id,
                    'status'       => $process->status->value,
                    'execute_type' => $process->execute_type,
                    'type'         => $process->type,
                    'client_request_id' => $process->client_request_id,
                    'created_at'   => $process->created_at?->toIso8601String(),
                    'updated_at'   => $process->updated_at?->toIso8601String(),
                ];
                $stepsPayload = $steps->map(static function (ProcessStep $step) {
                    return [
                        'id'                  => $step->id,
                        'process_id'          => $step->process_id,
                        'step_id'             => $step->step_id,
                        'template_step_order' => $step->template_step_order,
                        'assigned_user_id'    => $step->assigned_user_id,
                        'escalation_user_id'  => $step->escalation_user_id,
                        'status'              => $step->status->value,
                        'action_by'           => $step->action_by,
                        'acted_at'            => $step->acted_at?->toIso8601String(),
                        'created_at'          => $step->created_at?->toIso8601String(),
                        'updated_at'          => $step->updated_at?->toIso8601String(),
                    ];
                })->values()->all();

                $data['process'] = $processPayload;
                $data['process_steps'] = $stepsPayload;
                $data['workflow'] = [
                    'process'       => $processPayload,
                    'process_steps' => $stepsPayload,
                ];
            }
        }

        return $data;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function buildServiceTermTrees($serviceTerms): array
    {
        $result = [];
        
        foreach ($serviceTerms as $serviceTerm) {
            // Get the TermServiceSetting
            $termServiceSetting = $serviceTerm->termServiceSetting;
            
            if (!$termServiceSetting) continue;
            
            // Get all leaf term IDs from the JSON column
            $leafTermIds = $serviceTerm->term_ids ?? [];
            
            if (empty($leafTermIds)) continue;
            
            // Load all leaf terms with their full parent chain recursively
            $leafTerms = \Modules\Project\TermSetting\Models\TermSetting::query()
                ->whereIn('id', $leafTermIds)
                ->with(['parent' => function ($query) {
                    $query->with('parent.parent.parent.parent.parent.parent.parent.parent');
                }])
                ->get();
            
            // Build tree from leaf terms
            $termTree = $this->buildTermTreeFromLeafs($leafTerms);
            
            // Create service node with term tree as children
            $result[] = [
                'id' => $termServiceSetting->id,
                'name' => $termServiceSetting->name,
                'created_at' => $termServiceSetting->created_at?->toDateTimeString(),
                'updated_at' => $termServiceSetting->updated_at?->toDateTimeString(),
                'children' => $termTree,
            ];
        }
        
        return $result;
    }

    private function buildTermTreeFromLeafs($leafTerms): array
    {
        $trees = [];
        
        foreach ($leafTerms as $leafTerm) {
            // Build the path from leaf to root
            $path = $this->buildPathToRoot($leafTerm);
            
            // Merge the path into the tree
            $this->mergePathIntoTree($trees, $path);
        }
        
        return array_values($trees);
    }

    private function buildPathToRoot($term): array
    {
        $path = [];
        $current = $term;
        
        // Build path from leaf to root
        while ($current) {
            array_unshift($path, $current);
            $current = $current->parent;
        }
        
        return $path;
    }

    private function mergePathIntoTree(&$trees, array $path)
    {
        if (empty($path)) return;
        
        $rootId = $path[0]->id;
        
        // If root doesn't exist, create it
        if (!isset($trees[$rootId])) {
            $trees[$rootId] = $this->buildTermNode($path[0]);
        }
        
        // Merge the rest of the path
        $currentNode = &$trees[$rootId];
        for ($i = 1; $i < count($path); $i++) {
            $term = $path[$i];
            $found = false;
            
            // Check if this child already exists
            if (isset($currentNode['children'])) {
                foreach ($currentNode['children'] as &$child) {
                    if ($child['id'] == $term->id) {
                        $currentNode = &$child;
                        $found = true;
                        break;
                    }
                }
            }
            
            // If not found, add it
            if (!$found) {
                if (!isset($currentNode['children'])) {
                    $currentNode['children'] = [];
                }
                $newChild = $this->buildTermNode($term);
                $currentNode['children'][] = &$newChild;
                $currentNode = &$newChild;
            }
        }
    }

    private function buildTermNode($term): array
    {
        $node = [
            'id' => $term->id,
            'name' => $term->name ?? null,
            'description' => $term->description ?? null,
            'parent_id' => $term->parent_id,
            'is_active' => $term->is_active ?? null,
            'created_at' => $term->created_at?->toDateTimeString(),
            'children' => [],
        ];
        
        return $node;
    }
}
