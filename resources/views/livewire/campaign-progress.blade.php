<section title="Email Campaign Progress">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        @if($status === 'completed') bg-green-100 text-green-800
                        @elseif($status === 'sending') bg-blue-100 text-blue-800
                        @elseif($status === 'failed' || $status === 'cancelled') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($status) }}
                    </span>
                </div>

                <!-- Barre de progression -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progression</span>
                        <span class="text-sm text-gray-500">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300 ease-out" 
                             style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                <!-- Statistiques d'envoi -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-900">{{ $total }}</div>
                        <div class="text-sm text-gray-500">Total emails</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">{{ $processed }}</div>
                        <div class="text-sm text-gray-500">Envoyés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600">{{ $pending }}</div>
                        <div class="text-sm text-gray-500">En attente</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-red-600">{{ $failed }}</div>
                        <div class="text-sm text-gray-500">Échecs</div>
                    </div>
                </div>

                <!-- Métriques de tracking -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tracking</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $stats['opened_count'] }}</div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-blue-900">Ouvertures</div>
                                    <div class="text-xs text-blue-600">
                                        {{ $stats['open_rate'] }}% taux d'ouverture
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-green-600">{{ $stats['clicked_count'] }}</div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-green-900">Clics</div>
                                    <div class="text-xs text-green-600">
                                        {{ $stats['click_rate'] }}% taux de clic
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t pt-6 flex justify-between items-center">
                    <a href="{{ route('email.campaign.create') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Nouvelle campagne
                    </a>
                    
                    @if($status === 'sending')
                    <button wire:click="cancelCampaign" 
                            wire:confirm="Êtes-vous sûr de vouloir annuler cette campagne ?"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Annuler la campagne
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($status === 'sending')
        @push('scripts')
        <script>
            let progressInterval;
            
            progressInterval = setInterval(() => {
                $wire.pollProgress();
            }, 2000);

            document.addEventListener('livewire:will-morph', () => {
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
            });

            $wire.on('campaign-finished', () => {
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
            });
        </script>
        @endpush
    @endif
</section>