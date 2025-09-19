<div class="max-w-4xl mx-auto p-6 bg-white shadow-lg rounded-lg">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Créer une nouvelle campagne</h1>

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif
        
        <!-- Barre de progression -->
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                 style="width: {{ $this->getProgressPercentage() }}%"></div>
        </div>
        
        <!-- Indicateurs d'étapes -->
        <div class="flex justify-between items-center">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium border-2 
                        @if($i < $currentStep) bg-green-500 text-white border-green-500
                        @elseif($i == $currentStep) bg-blue-500 text-white border-blue-500
                        @else bg-gray-100 text-gray-500 border-gray-300 @endif">
                        @if($i < $currentStep)
                            ✓
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <span class="text-xs mt-1 @if($i == $currentStep) text-blue-600 font-medium @else text-gray-500 @endif">
                        @if($i == 1) Contenu
                        @elseif($i == 2) Infos
                        @elseif($i == 3) Prévisualiser
                        @endif
                    </span>
                </div>
                @if($i < $totalSteps)
                    <div class="flex-1 h-px bg-gray-300 mx-2"></div>
                @endif
            @endfor
        </div>
    </div>

    <div class="min-h-[400px]">
        @if($currentStep == 1)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">🎯 Expéditeur et Destinataires de la campagne</h2>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'expéditeur *</label>
                    <input type="text" wire:model="fromName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ex: L'équipe Newsletter">
                    @error('fromName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email de l'expéditeur *</label>
                    <input type="email" wire:model="fromEmail"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ex: newsletter@exemple.com">
                    @error('fromEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Upload du fichier -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">
                        Fichier d'emails (CSV ou TXT) *
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Sélectionner un fichier</span>
                                    <input wire:model="file" 
                                            id="file" 
                                            name="file" 
                                            type="file" 
                                            accept=".csv,.txt"
                                            class="sr-only">
                                </label>
                                <p class="pl-1">ou glisser-déposer</p>
                            </div>
                            <p class="text-xs text-gray-500">CSV, TXT jusqu'à 2MB</p>
                            
                            @if ($file)
                                <p class="text-sm text-green-600 mt-2">
                                    Fichier sélectionné: {{ $file->getClientOriginalName() }}
                                </p>
                            @endif
                        </div>
                    </div>
                    @error('file') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>
            </div>
        @endif

        @if($currentStep == 2)
            <div class="w-full space-y-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">✍️ Informations et contenu de la campagne</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                   <div>
                        <label for="campaignName" class="block text-sm font-medium text-gray-700">
                            Nom de la campagne (optionnel)
                        </label>
                        <input type="text" 
                                wire:model="campaignName"
                                id="campaignName"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ex: Newsletter Janvier 2025">
                        @error('campaignName') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>
                    
                    <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">
                        Sujet de l'email *
                    </label>
                    <input type="text" 
                            wire:model="subject"
                            id="subject"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ex: Découvrez nos nouveautés !">
                    @error('subject') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                <!-- Contenu -->
                <div class="w-full">
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Contenu de l'email (HTML autorisé) *
                    </label>
                    <textarea wire:model="content"
                                id="content"
                                rows="20"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Votre message ici..."></textarea>
                    @error('content') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    
                    <p class="mt-2 text-sm text-gray-500">
                        💡 Les liens seront automatiquement trackés pour mesurer les clics.
                    </p>
                </div>
            </div>
        @endif

        @if($currentStep == 3)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">📋 Prévisualiser</h2>
                
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
                    <div class="p-6 border-b">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium">👁️ Aperçu de l'email</h3>
                            <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="text-sm text-gray-600 mb-2">
                                <strong>De :</strong> <span x-text="'{{ $fromName }}' + ' <' + '{{ $fromEmail }}' + '>'"></span>
                            </div>
                            <div class="text-sm text-gray-600 mb-2">
                                <strong>Sujet :</strong> <span x-text="previewData?.subject || '{{ $subject }}'"></span>
                            </div>
                            @if($previewText)
                                <div class="text-xs text-gray-500 mb-4 italic">
                                    {{ $previewText }}
                                </div>
                            @endif
                            <div class="border-t pt-4">
                                <div class="prose max-w-none" x-html="previewData?.content || `{{ str_replace(['`', "\n"], ['\\`', '\\n'], $content) }}`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Navigation entre les étapes -->
    <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
        <div class="flex space-x-3">
            @if($currentStep > 1)
                <button wire:click="previousStep" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition">
                    ← Précédent
                </button>
            @endif
            
            <button wire:click="saveDraft" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition">
                💾 Sauver brouillon
            </button>
        </div>
        
        <div class="flex space-x-3">
            @if($currentStep < $totalSteps)
                <button wire:click="nextStep" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                    Suivant →
                </button>
            @else
                <button wire:click="createCampaign" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition">
                    🚀 Créer et envoyer
                </button>
            @endif
        </div>
    </div>

    <!-- Indicateur de chargement -->
    <div wire:loading.flex wire:target="nextStep,previousStep,createCampaign" 
         class="fixed inset-0 bg-gray-600 bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span>Traitement en cours...</span>
            </div>
        </div>
    </div>
</div>
