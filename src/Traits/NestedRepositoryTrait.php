<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;

trait NestedRepositoryTrait
{
    use ApiJsonResponse;

    public function addNested(Collection $input)
    {
        if (!InputHelper::null($input, 'parent_id')) {
            if (!InputHelper::null($input, 'ordering')) {
                $q = $input->get('q') ?: new QueryCapsule();
                $q = $q->where('parent_id', $input->get('parent_id'))
                    ->where('ordering', $input->get('ordering'));

                $selected = $this->search(collect(['q' => $q]))->first();
                $new      = $this->create($input->toArray());
                $selected ? $new->beforeNode($selected)->save() : true;
                $siblings = $new->getNextSiblings();

                return $this->siblingsOrderingChange($siblings, 'add') ? $new : false;
            } else {
                $parent     = $this->find($input->get('parent_id'));
                if (!$parent) {
                    throw new NotFoundException('ItemNotExist', [
                        'parent_id' => $input->get('parent_id')
                    ], null, $this->modelName);
                }
                $last_child = $parent->children->last();
                if ($last_child) {
                    $lastOrdering = $last_child->ordering + 1;
                    $input->put('ordering', $lastOrdering);
                    $new   = $this->create($input->toArray());

                    return $new->afterNode($last_child)->save() ? $new : false;
                } else {
                    $ordering =  1;
                    $input->put('ordering', $ordering);
                    $new   = $this->create($input->toArray());
                    return $parent->appendNode($new) ? $new : false;
                }
            }
        } else {
            # 代表 model = category
            if ($this->model->hasAttribute('extension')) {
                if($input->get('extension') != '') {
                    $q = new QueryCapsule();
                    $q = $q->where('title', 'ROOT')
                        ->where('extension', $input->get('extension'));
                    $parent = $this->search(collect(['q' => $q]))->first();
                    if (!$parent) {
                        throw new ForbiddenException('InvalidInput', [
                            'extension' => $input->get('extension'),
                            'parent_id' => null
                        ], null, $this->modelName);
                    }
                    $newNode = $this->create($input->toArray());

                    return $parent->appendNode($newNode) ? $newNode : false;
                } else {
                    throw new ForbiddenException('InvalidInput', [
                        'extension' => null,
                        'parent_id' => null
                    ], null, $this->modelName);
                }
            } else {
                $q = new QueryCapsule();
                $q =$q->whereNull('parent_id')
                    ->max('ordering');

                $lastOrdering = $this->search(collect(['q' => $q]));
                $input->put('ordering', $lastOrdering +1);

                return $this->create($input->toArray());
            }
        }
    }


    // 用在檢查多語言相同 path 狀況
    public function findMultiLanguageItem($input)
    {
        $language_options = ['*'];
        $language = !InputHelper::null($input, 'language') ? $input->get('language') : config('daydreamlab.global.locale');
        if ($language != '*') {
            $language_options[] = $language;
        }

        $query = $this->model;

        // table = menu
        if ($this->getModel()->hasAttribute('host')) {
            $query = $query
                ->where('host', $input->get('host'))
                ->whereIn('language', $language_options);
            $query = !InputHelper::null($input, 'path')
                ? $query->where('path', $input->get('path'))
                : $query;
        } else {
            if ($this->getModel()->hasAttribute('language')) {
                $query = $query
                    ->whereIn('language', $language_options);
                $query = !InputHelper::null($input, 'path')
                    ? $query->where('path', $input->get('path'))
                    : $query;
            } else {
                $query = $query->where('path', $input->get('path'));
            }
        }

        return $query->first();
    }


    public function findModifyOrderingTargetNode($input, $parent = null)
    {
        $q = new QueryCapsule();
        if ($parent) {
            $q = $q->where('parent_id', $parent->id);
        }

        if (in_array($input->get('ordering'), ['0', 0])) {
          $q = $q->limit(1)
            ->orderBy('ordering', 'asc');
        } elseif ($input->get('ordering')) {
            $q = $q->where('ordering', $input->get('ordering'));
        } else {
            $q = $q->limit(1)
                ->orderBy('ordering', 'desc');
        }

        return $this->search(collect(['q' => $q]))->first();
    }


    public function modifyNested(Collection $input, $parent, $item)
    {
        // 如果更換了 parent
        if ($item->parent_id != $input->get('parent_id')) {
            # 因更改了 paren，舊 node 的 nextSiblings 都會-1
            $item_next_siblings = $item->getNextSiblings();
            $this->siblingsOrderingChange($item_next_siblings, 'sub');
        }

        $targetNode = $this->findModifyOrderingTargetNode($input, $parent);
        if ($targetNode) {
            $item->parent_id = $targetNode->parent ? $targetNode->parent_id : null;
            if (in_array($input->get('ordering'), [0, '0'])) {
                $item->beforeNode($targetNode);
                $input->put('ordering', $input->get('ordering') + 1);
            } else {
                $item->afterNode($targetNode);
                $input->put('ordering', $targetNode->ordering + 1);
            }
        } else {
            if ($parent) {
                $input->put('ordering', $parent->children->count());
            } else {
                $q = new QueryCapsule();
                $q = $q->whereNull('parent_id');
                $input->put('ordering', $this->search(collect(['q' => $q]))->count() + 1);
            }
        }

        $result = $this->update($item, $this->getFillableInput($input));
        $this->siblingsOrderingChange($item->refresh()->getNextSiblings(), 'add');
        if (!$result) {
            throw new InternalServerErrorException('UpdateNestedFail', [], null, $this->modelName);
        }

        return $item->refresh();
    }


    public function removeNested($item)
    {
        $siblings = $item->getNextSiblings();
        $siblings->each(function ($item, $key) {
            $item->ordering--;
            $item->save();
        });

        $result = $this->delete($item, $item);

        return $result;
    }


    public function siblingsOrderingChange($siblings, $action = 'add')
    {
        foreach ($siblings as $sibling) {
            $action == 'add'
                ? $sibling->ordering++
                : $sibling->ordering--;

            if (!$sibling->save()) {
                throw new InternalServerErrorException('OrderingNestedFail', null, null, $this->modelName);
            }
        }

        return true;
    }
}
