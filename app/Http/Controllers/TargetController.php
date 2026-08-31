<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTargetRequest;
use App\Http\Requests\UpdateTargetRequest;
use App\Models\Target;
use App\Services\ActivityLogger;
use App\Services\TargetResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class TargetController extends Controller
{
    public function index(Request $request): View
    {
        $targets = Target::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('targets.index', compact('targets'));
    }

    public function create(): View
    {
        return view('targets.create');
    }

    public function store(StoreTargetRequest $request, TargetResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        return $this->persist($request, $resolver, $logger);
    }

    public function edit(Request $request, Target $target): View
    {
        abort_unless($target->user_id === $request->user()->id, 403);

        return view('targets.edit', compact('target'));
    }

    public function update(UpdateTargetRequest $request, Target $target, TargetResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($target->user_id === $request->user()->id, 403);

        return $this->persist($request, $resolver, $logger, $target);
    }

    public function destroy(Request $request, Target $target, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($target->user_id === $request->user()->id, 403);

        $name = $target->name;
        $target->delete();
        $logger->log('target.deleted', "Hedef silindi: {$name}");

        return redirect()->route('targets.index')->with('status', 'Hedef silindi.');
    }

    private function persist(
        StoreTargetRequest|UpdateTargetRequest $request,
        TargetResolver $resolver,
        ActivityLogger $logger,
        ?Target $target = null,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $resolver->resolve(
                $data['type'] === 'ip' ? ($data['ip'] ?? null) : null,
                $data['type'] === 'cidr' ? ($data['cidr'] ?? null) : null,
                $data['type'] === 'range' ? ($data['start_ip'] ?? null) : null,
                $data['type'] === 'range' ? ($data['end_ip'] ?? null) : null,
            );

            $payload = [
                'user_id' => $request->user()->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'ip' => $data['type'] === 'ip' ? ($data['ip'] ?? null) : null,
                'cidr' => $data['type'] === 'cidr' ? ($data['cidr'] ?? null) : null,
                'start_ip' => $data['type'] === 'range' ? ($data['start_ip'] ?? null) : null,
                'end_ip' => $data['type'] === 'range' ? ($data['end_ip'] ?? null) : null,
                'ports' => $data['ports'] ?: '22,80,443,3306',
                'notes' => $data['notes'] ?? null,
            ];

            if ($target) {
                $target->update($payload);
                $logger->log('target.updated', "Hedef güncellendi: {$target->name}", $target);

                return redirect()->route('targets.index')->with('status', 'Hedef güncellendi.');
            }

            $created = Target::query()->create($payload);
            $logger->log('target.created', "Hedef eklendi: {$created->name}", $created);

            return redirect()->route('targets.index')->with('status', 'Hedef kaydedildi.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['ip' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['ip' => 'Hedef kaydedilemedi. Bilgileri kontrol edin.']);
        }
    }
}
