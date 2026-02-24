<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SearchableSelect extends Component
{
    public $options;
    public $wireModel;
    public $label;
    public $placeholder;
    public $valueKey;
    public $labelKey;
    public $searchWireModel;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $options,
        $wireModel,
        $label = 'Select Option',
        $placeholder = 'Search...',
        $valueKey = 'id',
        $labelKey = 'name',
        $searchWireModel = null
    ) {
        $this->options = $options;
        $this->wireModel = $wireModel;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->valueKey = $valueKey;
        $this->labelKey = $labelKey;
        $this->searchWireModel = $searchWireModel;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.searchable-select');
    }
}
