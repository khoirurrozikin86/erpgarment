<?php

namespace App\Http\Middleware;

use App\Domain\Audit\Services\AuditLogService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AuditMutationRequests
{
    private const SENSITIVE_KEY_PATTERN = '/password|secret|token|credential|api[_-]?key|private[_-]?key/i';

    private const AUDITED_READ_ACTIONS = [
        'export', 'download', 'print', 'email', 'test', 'send',
    ];

    public function __construct(private AuditLogService $auditLog) {}

    public function handle(Request $request, Closure $next): Response
    {
        $actorId = $request->user()?->getAuthIdentifier();
        $response = $next($request);

        if (! $this->isAuditableRequest($request) || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($request->attributes->get('audit_logged')) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName() ?: $request->method().' '.$request->path();
        [$module, $action] = $this->classify($routeName, $request->method());
        $model = collect($route?->parameters() ?? [])->first(fn ($parameter) => $parameter instanceof Model);
        $inputs = $this->sanitize($request->except(['_token', '_method']));
        $userId = $request->user()?->getAuthIdentifier() ?? $actorId;

        try {
            $this->auditLog->log(
                action: $action,
                module: $module,
                description: "{$action} {$module} melalui {$routeName}",
                model: $model,
                newValues: $inputs === [] ? null : $inputs,
                userId: $userId === null ? null : (int) $userId,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function isAuditableRequest(Request $request): bool
    {
        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        $routeName = strtolower((string) $request->route()?->getName());
        foreach (self::AUDITED_READ_ACTIONS as $operation) {
            if (preg_match('/(^|[._-])'.preg_quote($operation, '/').'($|[._-])/', $routeName) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @return array{string, string} */
    private function classify(string $routeName, string $method): array
    {
        $parts = explode('.', strtolower($routeName));
        $parts = array_values(array_filter($parts, fn (string $part) => ! in_array($part, ['super', 'admin'], true)));
        $operation = end($parts) ?: strtolower($method);
        $route = strtolower($routeName);

        if (str_contains($route, '.permissions.')) {
            $action = 'SYNC_PERMISSIONS';
        } else {
            $action = match ($operation) {
                'store', 'create' => 'CREATE',
                'update', 'edit' => strtoupper($method) === 'GET' ? 'VIEW' : 'UPDATE',
                'destroy', 'delete' => 'DELETE',
                'import' => 'IMPORT',
                'export' => 'EXPORT',
                'download' => 'DOWNLOAD',
                'print' => 'PRINT',
                'approve' => 'APPROVE',
                'reject' => 'REJECT',
                'issue' => 'ISSUE',
                'post' => 'POST',
                'transition' => 'TRANSITION',
                'count', 'save' => 'SAVE',
                'email', 'send' => 'SEND',
                'test' => 'TEST',
                'sync' => 'SYNC',
                default => strtoupper($method),
            };
        }

        $moduleParts = array_slice($parts, 0, -1);
        if ($moduleParts === []) {
            $moduleParts = [$parts[0] ?? 'application'];
        }

        return [strtoupper(str_replace('-', '_', implode('_', $moduleParts))), $action];
    }

    private function sanitize(array $values, int $depth = 0): array
    {
        if ($depth > 5) {
            return ['__truncated' => true];
        }

        $result = [];
        foreach (array_slice($values, 0, 100, true) as $key => $value) {
            $key = (string) $key;
            if (preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $result[$key] = '[REDACTED]';
                continue;
            }
            if ($value instanceof UploadedFile) {
                continue;
            }
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value, $depth + 1);
                continue;
            }
            if (is_string($value)) {
                $result[$key] = mb_substr($value, 0, 4000);
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $result[$key] = $value;
            }
        }

        if (count($values) > 100) {
            $result['__truncated_count'] = count($values) - 100;
        }

        return $result;
    }
}
