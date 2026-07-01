@props(['item', 'align' => 'left'])
@php
    $children = $item->childrenRecursive ?? collect();
    $isShopMega = str($item->label ?? '')->lower()->squish()->toString() === 'shop products';
@endphp
<div @class(['np-menu-item', 'np-menu-item-right' => $align === 'right'])>
    <a
        href="{{ $item->resolvedUrl() }}"
        target="{{ $item->target }}"
        @if($item->target === '_blank') rel="noopener noreferrer" @endif
        class="np-menu-link {{ $item->css_class }}"
        @if($children->isNotEmpty()) aria-haspopup="true" aria-expanded="false" @endif
    >
        <span>{{ $item->label }}</span>
        @if($children->isNotEmpty())
            <span class="np-menu-caret" aria-hidden="true">▾</span>
        @endif
    </a>

    @if($children->isNotEmpty())
        <div @class(['np-menu-panel', 'np-shop-panel' => $isShopMega]) role="group" aria-label="{{ $item->label }} submenu">
            @if($item->resolvedUrl() !== '#')
                <a
                    class="np-menu-view-all"
                    href="{{ $item->resolvedUrl() }}"
                    target="{{ $item->target }}"
                    @if($item->target === '_blank') rel="noopener noreferrer" @endif
                >
                    <span>View all {{ $item->label }}</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endif

            <div @class(['np-mega-grid', 'np-standard-grid' => ! $isShopMega])>
                @foreach($children as $child)
                    <div class="np-mega-card">
                        <a
                            href="{{ $child->resolvedUrl() }}"
                            target="{{ $child->target }}"
                            @if($child->target === '_blank') rel="noopener noreferrer" @endif
                            class="np-mega-title"
                        >{{ $child->label }}</a>

                        @if($child->childrenRecursive->isNotEmpty())
                            <div class="np-mega-sublist">
                                @foreach($child->childrenRecursive as $grandchild)
                                    <div class="np-mega-subitem">
                                        <a
                                            href="{{ $grandchild->resolvedUrl() }}"
                                            target="{{ $grandchild->target }}"
                                            @if($grandchild->target === '_blank') rel="noopener noreferrer" @endif
                                            class="np-mega-subtitle"
                                        >{{ $grandchild->label }}</a>

                                        @if($grandchild->childrenRecursive->isNotEmpty())
                                            <div class="np-mega-leaf-list">
                                                @foreach($grandchild->childrenRecursive as $leaf)
                                                    <a
                                                        href="{{ $leaf->resolvedUrl() }}"
                                                        target="{{ $leaf->target }}"
                                                        @if($leaf->target === '_blank') rel="noopener noreferrer" @endif
                                                        class="np-mega-leaf"
                                                    >{{ $leaf->label }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
