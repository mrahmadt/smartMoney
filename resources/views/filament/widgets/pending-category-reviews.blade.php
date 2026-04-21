<div>
    @if(count($this->reviews) > 0)
    <div style="border: 1px solid #fbbf24; background: #fffbeb; border-radius: 12px; padding: 12px 16px; margin-bottom: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <p style="font-size: 14px; font-weight: 600; color: #92400e; margin: 0;">
                {{ __('menu.pending_category_reviews') }}
                <span style="background: #f59e0b; color: white; border-radius: 9999px; padding: 1px 8px; font-size: 12px; margin-inline-start: 4px;">{{ count($this->reviews) }}</span>
            </p>
            <a href="{{ \App\Filament\Pages\ReviewCategories::getUrl() }}" style="font-size: 12px; color: #d97706; text-decoration: underline;">{{ __('menu.view_all') }}</a>
        </div>

        @foreach($this->reviews as $review)
        <div wire:key="review-{{ $review['id'] }}" style="display: flex; align-items: center; gap: 12px; padding: 8px 0; border-top: 1px solid #fde68a;">
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $review['account_name'] }}</span>
                    <span style="font-size: 12px; color: #6b7280;">{{ $review['date'] }}</span>
                    <span style="font-size: 13px; color: #dc2626; font-weight: 500;">{{ $review['amount'] }} {{ $review['currency_code'] }}</span>
                    <span style="font-size: 11px; background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 4px;">{{ $review['current_category'] }}</span>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap;">
                    @foreach($review['alternatives'] as $catId => $catName)
                        <span wire:click="applyAlternative({{ $review['id'] }}, {{ $catId }})"
                              style="font-size: 11px; background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 6px; cursor: pointer; border: 1px solid #bbf7d0;"
                              class="hover:opacity-80">
                            {{ $catName }}
                        </span>
                    @endforeach
                </div>
            </div>
            <button wire:click="dismissReview({{ $review['id'] }})" style="background: none; border: none; cursor: pointer; padding: 2px; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#6b7280" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endforeach
    </div>
    @endif
</div>
