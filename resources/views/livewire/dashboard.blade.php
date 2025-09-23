<div class="space-y-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Tableau de bord</h2>
            <p class="text-gray-600 mb-6">
                Gérez vos campagnes d'emails en masse avec tracking avancé.
            </p>
            
            <div class="flex space-x-4">
                <a href="{{ route('email.campaign.create') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
                    Créer une nouvelle campagne
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Campagnes totales
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $stats['total_campaigns'] }}
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Emails envoyés
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $stats['total_delivered'] }}
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Abonnés
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $active_subscribers }}
                </dd>
            </div>
        </div>
    </div>

    <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance des emails</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_opened'] }}</div>
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
                    <div class="text-2xl font-bold text-green-600">{{ $stats['total_clicked'] }}</div>
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
</div>