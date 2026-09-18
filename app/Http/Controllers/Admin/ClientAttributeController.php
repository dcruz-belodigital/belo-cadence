<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ClientAttributes\CreateClientAttributeAction;
use App\Actions\ClientAttributes\DeleteClientAttributeAction;
use App\Actions\ClientAttributes\UpdateClientAttributeAction;
use App\Data\ClientAttributes\ClientAttributeFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientAttributes\StoreClientAttributeRequest;
use App\Http\Requests\ClientAttributes\UpdateClientAttributeRequest;
use App\Models\ClientAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The fields a team records about a client, managed as a resource of their own.
 *
 * Every listing counts the answers recorded against each attribute, because that count
 * is what makes deleting one an informed decision rather than a surprise.
 */
final class ClientAttributeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClientAttribute::class);

        $filters = ClientAttributeFilters::fromRequest($request);

        return view('admin.client-attributes.index', [
            'filters' => $filters,
            'attributes' => ClientAttribute::query()
                ->filtered($filters)
                ->withCount('values')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ClientAttribute::class);

        return view('admin.client-attributes.create', [
            'nextPosition' => (int) ClientAttribute::query()->max('position') + 1,
        ]);
    }

    public function store(StoreClientAttributeRequest $request, CreateClientAttributeAction $createAttribute): RedirectResponse
    {
        $this->authorize('create', ClientAttribute::class);

        $attribute = $createAttribute($request->toData());

        return redirect()
            ->route('admin.client-attributes.show', $attribute)
            ->with('success', __('client_attributes.flash.created', ['name' => $attribute->name]));
    }

    public function show(ClientAttribute $attribute): View
    {
        $this->authorize('view', $attribute);

        $attribute->loadCount('values');

        return view('admin.client-attributes.show', ['attribute' => $attribute]);
    }

    public function edit(ClientAttribute $attribute): View
    {
        $this->authorize('update', $attribute);

        return view('admin.client-attributes.edit', ['attribute' => $attribute]);
    }

    public function update(
        UpdateClientAttributeRequest $request,
        ClientAttribute $attribute,
        UpdateClientAttributeAction $updateAttribute,
    ): RedirectResponse {
        $this->authorize('update', $attribute);

        $updateAttribute($attribute, $request->toData());

        return redirect()
            ->route('admin.client-attributes.show', $attribute)
            ->with('success', __('client_attributes.flash.updated', ['name' => $attribute->name]));
    }

    public function destroy(ClientAttribute $attribute, DeleteClientAttributeAction $deleteAttribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $name = $attribute->name;

        $deleteAttribute($attribute);

        return redirect()
            ->route('admin.client-attributes.index')
            ->with('success', __('client_attributes.flash.deleted', ['name' => $name]));
    }
}
