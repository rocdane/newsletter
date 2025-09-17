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
                    {{ $total_campaigns }}
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Emails envoyés
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $total_sent }}
                </dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">
                    Suscribers actifs
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $active_suscribers }}
                </dd>
            </div>
        </div>
    </div>
</div>