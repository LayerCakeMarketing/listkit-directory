<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\ListItem;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListItemController extends Controller
{
    /**
     * Add an item to a list
     */
    public function store(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:directory_entry,text,location,event,section',
            'directory_entry_id' => 'required_if:type,directory_entry|exists:places,id',
            'title' => 'required_if:type,text,location,event,section|string|max:255',
            'content' => 'nullable|string',
            'data' => 'nullable|array',
            'section_id' => 'nullable|string',
            'affiliate_url' => 'nullable|url',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Convert to section format if needed
            if ($list->structure_version !== '2.0') {
                $list->convertToSectionFormat();
            }

            // Get the next order index
            $maxOrder = $list->items()->max('order_index') ?? -1;
            
            $itemData = [
                'list_id' => $list->id,
                'type' => $validated['type'],
                'order_index' => $maxOrder + 1,
                'data' => $validated['data'] ?? []
            ];

            // Handle different item types
            switch ($validated['type']) {
                case 'directory_entry':
                    $place = Place::findOrFail($validated['directory_entry_id']);
                    $itemData['directory_entry_id'] = $place->id;
                    // Don't set display_title/display_content - these are computed properties
                    break;
                    
                case 'text':
                case 'location':
                case 'event':
                    $itemData['title'] = $validated['title'];
                    $itemData['content'] = $validated['content'] ?? '';
                    if ($validated['type'] === 'location' || $validated['type'] === 'event') {
                        $itemData['data'] = array_merge($itemData['data'], $validated['data'] ?? []);
                    }
                    break;
                    
                case 'section':
                    $itemData['title'] = $validated['title'];
                    $itemData['is_section'] = true;
                    break;
            }

            // Add common fields
            if (isset($validated['affiliate_url'])) {
                $itemData['affiliate_url'] = $validated['affiliate_url'];
            }
            if (isset($validated['notes'])) {
                $itemData['notes'] = $validated['notes'];
            }

            $item = ListItem::create($itemData);
            
            // Load relationships for response
            if ($item->type === 'directory_entry') {
                $item->load('directoryEntry');
            }

            DB::commit();

            return response()->json([
                'message' => 'Item added successfully',
                'item' => $item
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding item to list: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to add item'], 500);
        }
    }

    /**
     * Update an item
     */
    public function update(Request $request, $listId, $itemId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item = $list->items()->findOrFail($itemId);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'data' => 'nullable|array',
            'affiliate_url' => 'nullable|url',
            'notes' => 'nullable|string',
            'item_image' => 'nullable|string',
            'item_image_url' => 'nullable|url'
        ]);

        // Update based on item type
        if ($item->type !== 'directory_entry' && isset($validated['title'])) {
            $item->title = $validated['title'];
        }
        
        if (in_array($item->type, ['text', 'event']) && isset($validated['content'])) {
            $item->content = $validated['content'];
        }
        
        if (isset($validated['data'])) {
            $item->data = array_merge($item->data ?? [], $validated['data']);
        }
        
        if (isset($validated['affiliate_url'])) {
            $item->affiliate_url = $validated['affiliate_url'];
        }
        
        if (isset($validated['notes'])) {
            $item->notes = $validated['notes'];
        }
        
        if (isset($validated['item_image'])) {
            $item->item_image_cloudflare_id = $validated['item_image'];
        }
        
        if (isset($validated['item_image_url'])) {
            $item->item_image_url = $validated['item_image_url'];
        }

        $item->save();

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item
        ]);
    }

    /**
     * Remove an item
     */
    public function destroy($listId, $itemId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item = $list->items()->findOrFail($itemId);
        $item->delete();

        // Reindex remaining items
        $list->items()->where('order_index', '>', $item->order_index)
            ->decrement('order_index');

        return response()->json(['message' => 'Item removed successfully']);
    }

    /**
     * Reorder items
     */
    public function reorder(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:list_items,id',
            'items.*.order_index' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                ListItem::where('id', $itemData['id'])
                    ->where('list_id', $list->id)
                    ->update(['order_index' => $itemData['order_index']]);
            }
            
            DB::commit();
            
            return response()->json(['message' => 'Items reordered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error reordering items: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reorder items'], 500);
        }
    }

    /**
     * Add saved items to list
     */
    public function addSavedItems(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:place,region,userlist',
            // Legacy support for place_ids
            'place_ids' => 'array',
            'place_ids.*' => 'exists:directory_entries,id'
        ]);

        DB::beginTransaction();
        try {
            $addedItems = [];
            $maxOrder = $list->items()->max('order_index') ?? -1;
            
            // Handle new format with multiple types
            if (isset($validated['items'])) {
                foreach ($validated['items'] as $index => $itemData) {
                    $newItem = null;
                    
                    switch ($itemData['type']) {
                        case 'place':
                            $place = Place::find($itemData['id']);
                            if ($place) {
                                $newItem = ListItem::create([
                                    'list_id' => $list->id,
                                    'type' => 'directory_entry',
                                    'directory_entry_id' => $place->id,
                                    'order_index' => $maxOrder + 1 + $index,
                                    'section_id' => $request->input('section_id')
                                ]);
                                $newItem->load('directoryEntry');
                            }
                            break;
                            
                        case 'region':
                            $region = \App\Models\Region::find($itemData['id']);
                            if ($region) {
                                $newItem = ListItem::create([
                                    'list_id' => $list->id,
                                    'type' => 'region',
                                    'title' => $region->name,
                                    'content' => $region->description,
                                    'data' => [
                                        'region_id' => $region->id,
                                        'region_name' => $region->name,
                                        'region_type' => $region->type,
                                        'region_slug' => $region->slug
                                    ],
                                    'order_index' => $maxOrder + 1 + $index,
                                    'section_id' => $request->input('section_id')
                                ]);
                            }
                            break;
                            
                        case 'userlist':
                            $linkedList = UserList::find($itemData['id']);
                            if ($linkedList && $linkedList->id !== $list->id) { // Prevent self-reference
                                $newItem = ListItem::create([
                                    'list_id' => $list->id,
                                    'type' => 'list',
                                    'title' => $linkedList->name,
                                    'content' => $linkedList->description,
                                    'data' => [
                                        'list_id' => $linkedList->id,
                                        'list_name' => $linkedList->name,
                                        'list_slug' => $linkedList->slug,
                                        'author' => $linkedList->user->name
                                    ],
                                    'order_index' => $maxOrder + 1 + $index,
                                    'section_id' => $request->input('section_id')
                                ]);
                            }
                            break;
                    }
                    
                    if ($newItem) {
                        $addedItems[] = $newItem;
                    }
                }
            }
            // Legacy support for place_ids
            elseif (isset($validated['place_ids'])) {
                foreach ($validated['place_ids'] as $index => $placeId) {
                    $place = Place::find($placeId);
                    if ($place) {
                        $item = ListItem::create([
                            'list_id' => $list->id,
                            'type' => 'directory_entry',
                            'directory_entry_id' => $place->id,
                            'order_index' => $maxOrder + 1 + $index
                        ]);
                        
                        $item->load('directoryEntry');
                        $addedItems[] = $item;
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => count($addedItems) . ' items added successfully',
                'items' => $addedItems
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding saved items: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to add saved items'], 500);
        }
    }

    /**
     * Create a new section
     */
    public function createSection(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'heading' => 'required|string|max:255',
            'after_index' => 'nullable|integer'
        ]);

        DB::beginTransaction();
        try {
            // Convert to section format if needed
            if ($list->structure_version !== '2.0') {
                $list->convertToSectionFormat();
            }

            $orderIndex = $validated['after_index'] ?? $list->items()->max('order_index') ?? -1;
            
            // Shift items after the insertion point
            $list->items()->where('order_index', '>', $orderIndex)
                ->increment('order_index');

            $section = ListItem::create([
                'list_id' => $list->id,
                'type' => 'section',
                'title' => $validated['heading'],
                'is_section' => true,
                'order_index' => $orderIndex + 1
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Section created successfully',
                'section' => $section
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating section: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create section'], 500);
        }
    }

    /**
     * Convert list to sections format
     */
    public function convertToSections(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sections = $list->convertToSectionFormat();
        
        return response()->json([
            'message' => 'List converted to sections format',
            'list' => $list,
            'sections' => $sections
        ]);
    }

    /**
     * Update section heading
     */
    public function updateSection(Request $request, $listId, $sectionId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'heading' => 'required|string|max:255'
        ]);

        $section = ListItem::where('list_id', $list->id)
            ->where('id', $sectionId)
            ->where('is_section', true)
            ->firstOrFail();

        $section->update(['title' => $validated['heading']]);

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section
        ]);
    }

    /**
     * Delete a section
     */
    public function deleteSection(Request $request, $listId, $sectionId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $section = ListItem::where('list_id', $list->id)
                ->where('id', $sectionId)
                ->where('is_section', true)
                ->firstOrFail();

            // Move items from this section to default or delete them
            ListItem::where('list_id', $list->id)
                ->where('section_id', $sectionId)
                ->update(['section_id' => null]);

            $section->delete();

            DB::commit();

            return response()->json([
                'message' => 'Section deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete section'], 500);
        }
    }

    /**
     * Reorder sections
     */
    public function reorderSections(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);
        
        if (!$list->canEdit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:list_items,id',
            'sections.*.order_index' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['sections'] as $sectionData) {
                ListItem::where('id', $sectionData['id'])
                    ->where('list_id', $list->id)
                    ->where('is_section', true)
                    ->update(['order_index' => $sectionData['order_index']]);
            }

            DB::commit();

            return response()->json(['message' => 'Sections reordered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to reorder sections'], 500);
        }
    }
}