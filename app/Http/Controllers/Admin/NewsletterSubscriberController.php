<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'source' => trim((string) $request->query('source', '')),
        ];

        $query = NewsletterSubscriber::query()->latest('subscribed_at')->latest('id');

        $this->applyFilters($query, $filters);

        $subscribers = $query->paginate($this->adminPerPage(30))->withQueryString();

        $stats = [
            'total' => NewsletterSubscriber::query()->count(),
            'active' => NewsletterSubscriber::query()->where('status', NewsletterSubscriber::STATUS_ACTIVE)->count(),
            'unsubscribed' => NewsletterSubscriber::query()->where('status', NewsletterSubscriber::STATUS_UNSUBSCRIBED)->count(),
            'today' => NewsletterSubscriber::query()->whereDate('subscribed_at', now()->toDateString())->count(),
        ];

        $sources = NewsletterSubscriber::query()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('admin.newsletter-subscribers.index', compact('subscribers', 'filters', 'stats', 'sources'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'source' => trim((string) $request->query('source', '')),
        ];

        $query = NewsletterSubscriber::query()->latest('subscribed_at')->latest('id');
        $this->applyFilters($query, $filters);

        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Email',
                'Status',
                'Source',
                'Subscribed At',
                'Unsubscribed At',
                'Created At',
                'Updated At',
            ]);

            $query->chunk(300, function ($subscribers) use ($handle): void {
                foreach ($subscribers as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->status,
                        $subscriber->source,
                        optional($subscriber->subscribed_at)->format('Y-m-d H:i:s'),
                        optional($subscriber->unsubscribed_at)->format('Y-m-d H:i:s'),
                        optional($subscriber->created_at)->format('Y-m-d H:i:s'),
                        optional($subscriber->updated_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<NewsletterSubscriber> $query
     * @param array{q:string,status:string,source:string} $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('email', 'like', '%'.$search.'%')
                    ->orWhere('source', 'like', '%'.$search.'%');
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['source'] !== '') {
            $query->where('source', $filters['source']);
        }
    }
}
