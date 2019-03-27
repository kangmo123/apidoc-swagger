<?php

namespace App\Providers;

use App\Repositories\Area\AreaHybridRepository;
use App\Repositories\Area\AreaRepository;
use App\Repositories\Centre\CentreHybridRepository;
use App\Repositories\Centre\CentreRepository;
use App\Repositories\Client\ClientHybridRepository;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Department\DepartmentHybridRepository;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\MerchantProjects\MerchantProjectAPIRepository;
use App\Repositories\MerchantProjects\MerchantProjectRepository;
use App\Repositories\MerchantTags\MerchantTagDBRepository;
use App\Repositories\MerchantTags\MerchantTagRepository;
use App\Repositories\Nation\NationHybridRepository;
use App\Repositories\Nation\NationRepository;
use App\Repositories\Project\ProjectHybridRepository;
use App\Repositories\Project\ProjectRepository;
use App\Repositories\Sale\SaleHybridRepository;
use App\Repositories\Sale\SaleRepository;
use App\Repositories\Team\TeamHybridRepository;
use App\Repositories\Team\TeamRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register Repositories
     *
     * @return void
     */
    public function register(): void
    {
        //Forecast
        $this->app->bind(SaleRepository::class, SaleHybridRepository::class);
        $this->app->bind(TeamRepository::class, TeamHybridRepository::class);
        $this->app->bind(CentreRepository::class, CentreHybridRepository::class);
        $this->app->bind(AreaRepository::class, AreaHybridRepository::class);
        $this->app->bind(DepartmentRepository::class, DepartmentHybridRepository::class);
        $this->app->bind(NationRepository::class, NationHybridRepository::class);
        $this->app->bind(ClientRepository::class, ClientHybridRepository::class);
        $this->app->bind(ProjectRepository::class, ProjectHybridRepository::class);
        // 招商项目-招商标签-政策标签
        $this->app->bind(MerchantTagRepository::class, MerchantTagDBRepository::class);
        $this->app->bind(MerchantProjectRepository::class, MerchantProjectAPIRepository::class);
    }
}
