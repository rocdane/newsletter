<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Créer une campagne d'emails</h2>

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

            <form wire:submit.prevent="submit" class="space-y-6">
                <!-- Nom de la campagne -->
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

                    <!-- Sujet -->
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
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Contenu de l'email (HTML autorisé) *
                    </label>
                    <textarea wire:model="content"
                                id="content"
                                rows="10"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Votre message ici..."></textarea>
                    @error('content') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    
                    <p class="mt-2 text-sm text-gray-500">
                        💡 Les liens seront automatiquement trackés pour mesurer les clics.
                    </p>
                </div>

                <!-- Bouton de soumission -->
                <div class="flex justify-end">
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-md font-medium flex items-center">
                        <div wire:loading wire:target="submit" class="mr-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span wire:loading.remove wire:target="submit">Créer et lancer la campagne</span>
                        <span wire:loading wire:target="submit">Création en cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
