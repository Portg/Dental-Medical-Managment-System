<?php

namespace App\Http\Helper;

/**
 * ActionColumnHelper - Unified action column builder for DataTable list pages.
 *
 * Generates a unified action dropdown for DataTable rows.
 * - Only primary → single button
 * - Primary + items → dropdown (primary as first bold item)
 * - Items only → dropdown with "Action" label
 *
 * Usage:
 *   // Simple: edit as primary, delete in dropdown
 *   ActionColumnHelper::make($row->id)->primary('edit')->add('delete')->render();
 *
 *   // Custom labels and onclick
 *   ActionColumnHelper::make($row->id)
 *       ->primary('edit', __('common.edit'))
 *       ->add('preview', __('templates.preview'), '#', 'previewTemplate')
 *       ->add('delete')
 *       ->render();
 *
 *   // Conditional actions
 *   ActionColumnHelper::make($row->id)
 *       ->primaryIf($row->deleted_at == null, 'edit')
 *       ->addIf($row->status == 'Pending', 'approve', __('common.approve'), '#', 'approveRecord')
 *       ->add('delete')
 *       ->render();
 *
 *   // Raw HTML insertion
 *   ActionColumnHelper::make($row->id)
 *       ->primary('edit')
 *       ->addRaw($customHtml)
 *       ->add('delete')
 *       ->render();
 */
class ActionColumnHelper
{
    /** @var int|string */
    protected $id;

    /** @var array|null Primary action: ['label' => '', 'href' => '', 'onclick' => ''] */
    protected $primary = null;

    /** @var array Dropdown items: each is ['label' => '', 'href' => '', 'onclick' => '', 'raw' => ''] */
    protected $items = [];

    /** @var array Preset action definitions: name => [label_key, onclick_pattern] */
    protected static $presets = [
        'edit'   => ['common.edit',   'editRecord'],
        'delete' => ['common.delete', 'deleteRecord'],
        'view'   => ['common.view',   'viewRecord'],
    ];

    /**
     * Create a new ActionColumnHelper instance.
     *
     * @param int|string $id The record ID
     * @return static
     */
    public static function make($id)
    {
        $instance = new static();
        $instance->id = $id;
        return $instance;
    }

    /**
     * Set the primary (main) action button.
     *
     * @param string      $name    Preset name (edit/delete/view) or custom name
     * @param string|null $label   Custom label (auto-resolved for presets)
     * @param string|null $href    URL (default '#')
     * @param string|null $onclick JS function name (auto-resolved for presets)
     * @return $this
     */
    public function primary($name, $label = null, $href = null, $onclick = null)
    {
        $this->primary = $this->resolveAction($name, $label, $href, $onclick);
        return $this;
    }

    /**
     * Set the primary action only if condition is true.
     *
     * @param bool        $condition
     * @param string      $name
     * @param string|null $label
     * @param string|null $href
     * @param string|null $onclick
     * @return $this
     */
    public function primaryIf($condition, $name, $label = null, $href = null, $onclick = null)
    {
        if ($condition) {
            return $this->primary($name, $label, $href, $onclick);
        }
        return $this;
    }

    /**
     * Add a dropdown menu item.
     *
     * @param string      $name    Preset name or custom name
     * @param string|null $label   Custom label
     * @param string|null $href    URL (default '#')
     * @param string|null $onclick JS function name
     * @return $this
     */
    public function add($name, $label = null, $href = null, $onclick = null)
    {
        $this->items[] = $this->resolveAction($name, $label, $href, $onclick);
        return $this;
    }

    /**
     * Add a dropdown item only if condition is true.
     *
     * @param bool        $condition
     * @param string      $name
     * @param string|null $label
     * @param string|null $href
     * @param string|null $onclick
     * @return $this
     */
    public function addIf($condition, $name, $label = null, $href = null, $onclick = null)
    {
        if ($condition) {
            return $this->add($name, $label, $href, $onclick);
        }
        return $this;
    }

    /**
     * Add raw HTML as a dropdown item (for complex pre-built markup).
     *
     * @param string $html
     * @return $this
     */
    public function addRaw($html)
    {
        $this->items[] = ['raw' => $html];
        return $this;
    }

    /**
     * Render the action column HTML.
     *
     * Rendering rules:
     * - Only primary, no dropdown items → single button
     * - Primary + dropdown items → dropdown button (primary as bold first item)
     * - No primary → dropdown button with "Action" label
     *
     * @return string
     */
    public function render()
    {
        $hasDropdown = count($this->items) > 0;

        // No primary, no items → empty
        if (!$this->primary && !$hasDropdown) {
            return '';
        }

        // Only primary, no dropdown → single button
        if ($this->primary && !$hasDropdown) {
            return $this->renderSingleButton($this->primary);
        }

        // Primary + dropdown → split button group
        if ($this->primary && $hasDropdown) {
            return $this->renderSplitGroup();
        }

        // No primary, has dropdown → pure dropdown
        return $this->renderPureDropdown();
    }

    /**
     * Resolve an action definition from preset or custom parameters.
     *
     * @param string      $name
     * @param string|null $label
     * @param string|null $href
     * @param string|null $onclick
     * @return array
     */
    protected function resolveAction($name, $label, $href, $onclick)
    {
        if (isset(self::$presets[$name])) {
            $preset = self::$presets[$name];
            return [
                'label'   => $label ?? __($preset[0]),
                'href'    => $href ?? '#',
                'onclick' => $onclick ?? $preset[1],
            ];
        }

        return [
            'label'   => $label ?? $name,
            'href'    => $href ?? '#',
            'onclick' => $onclick,
        ];
    }

    /**
     * Render a single button (no dropdown).
     *
     * @param array $action
     * @return string
     */
    protected function renderSingleButton($action)
    {
        $attrs = $this->buildLinkAttrs($action);
        return '<a ' . $attrs . ' class="btn btn-sm btn-primary">' . e($action['label']) . '</a>';
    }

    /**
     * Render a dropdown button with primary action as first bold item.
     *
     * @return string
     */
    protected function renderSplitGroup()
    {
        $html = '<div class="btn-group">';
        $html .= '<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">';
        $html .= e(__('common.action'));
        $html .= ' <span class="caret"></span>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';
        // Primary as first item
        $attrs = $this->buildLinkAttrs($this->primary);
        $html .= '<li><a ' . $attrs . '>' . e($this->primary['label']) . '</a></li>';
        $html .= $this->renderDropdownItems();
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render a pure dropdown (no primary button).
     *
     * @return string
     */
    protected function renderPureDropdown()
    {
        $html = '<div class="btn-group">';
        $html .= '<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">';
        $html .= e(__('common.action'));
        $html .= ' <span class="caret"></span>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';
        $html .= $this->renderDropdownItems();
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render all dropdown <li> items.
     *
     * @return string
     */
    protected function renderDropdownItems()
    {
        $html = '';
        foreach ($this->items as $item) {
            if (isset($item['raw'])) {
                $html .= $item['raw'];
            } else {
                $attrs = $this->buildLinkAttrs($item);
                $html .= '<li><a ' . $attrs . '>' . e($item['label']) . '</a></li>';
            }
        }
        return $html;
    }

    /**
     * Build href and onclick attributes for an action link.
     *
     * @param array $action
     * @return string
     */
    protected function buildLinkAttrs($action)
    {
        $parts = [];
        $parts[] = 'href="' . e($action['href']) . '"';

        if (!empty($action['onclick'])) {
            $parts[] = 'onclick="' . e($action['onclick']) . '(' . e($this->id) . ')"';
        }

        return implode(' ', $parts);
    }
}
