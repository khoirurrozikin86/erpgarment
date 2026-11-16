<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

class ServerSideTable
{
    public function response(Request $request, array $filters, callable $builder, array $columns, string $rowsView, array $viewData = [], string $variable = 'items')
    {
        $input = $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'in:10,25,50,100'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'order.0.column' => ['nullable', 'integer', 'min:0'],
            'order.0.dir' => ['nullable', 'in:asc,desc'],
        ]);
        $base = $builder($filters);
        $countQuery = $base instanceof EloquentBuilder ? (clone $base)->toBase() : clone $base;
        $total = $countQuery->getCountForPagination();
        $filters['search'] = $input['search']['value'] ?? '';
        $query = $builder($filters);
        $column = $columns[$input['order'][0]['column'] ?? -1] ?? null;
        if ($column !== null) {
            $query->reorder()->orderByRaw($column.' '.($input['order'][0]['dir'] ?? 'asc'));
        }
        $items = $query->paginate($input['length'], ['*'], 'page', intdiv($input['start'], $input['length']) + 1);
        $html = view($rowsView, array_merge($viewData, [$variable => $items]))->render();
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><table><tbody>'.$html.'</tbody></table>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $data = [];
        foreach ((new DOMXPath($document))->query('//tbody/tr') as $row) {
            $cells = [];
            $classes = [];
            foreach ($row->childNodes as $cell) {
                if ($cell->nodeName !== 'td') continue;
                $content = '';
                foreach ($cell->childNodes as $child) $content .= $document->saveHTML($child);
                $cells[] = $content;
                $classes[] = $cell->getAttribute('class');
            }
            $data[] = ['cells' => $cells, 'classes' => $classes];
        }
        return response()->json([
            'draw' => (int) $input['draw'], 'recordsTotal' => $total,
            'recordsFiltered' => $items->total(), 'data' => $data,
        ]);
    }
}
