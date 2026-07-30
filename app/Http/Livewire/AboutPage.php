<?php

namespace App\Http\Livewire;

use Livewire\Component;

class AboutPage extends Component
{
    public function render()
    {
        return view('legal.about')
            ->layout('layouts.app', ['title' => 'À propos']);
    }
}
