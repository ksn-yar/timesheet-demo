<template>
  <div class="d-flex" style="min-height: 100vh;">
    <nav class="d-flex flex-column p-3 text-white flex-shrink-0" style="width: 220px; background: #1e2a3a; min-height: 100vh;">
      <div class="mb-4 px-2">
        <span class="fs-6 fw-bold text-white">
          <i class="bi bi-clock-history me-2"></i>Timesheet
        </span>
      </div>

      <ul class="nav flex-column gap-1 flex-grow-1">
        <li class="nav-item">
          <NuxtLink to="/" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-house me-2"></i>Главная
          </NuxtLink>
        </li>

        <li class="nav-item mt-3">
          <span class="px-2 text-white-50 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.08em;">
            Идентификация
          </span>
        </li>
        <li class="nav-item">
          <NuxtLink to="/identity/users" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-people me-2"></i>Пользователи
          </NuxtLink>
        </li>
        <li class="nav-item">
          <NuxtLink to="/identity/groups" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-collection me-2"></i>Группы
          </NuxtLink>
        </li>

        <li class="nav-item mt-3">
          <span class="px-2 text-white-50 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.08em;">
            Проекты
          </span>
        </li>
        <li class="nav-item">
          <NuxtLink to="/pm/clients" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-building me-2"></i>Клиенты
          </NuxtLink>
        </li>
        <li class="nav-item">
          <NuxtLink to="/pm/projects" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-kanban me-2"></i>Проекты
          </NuxtLink>
        </li>
        <li class="nav-item">
          <NuxtLink to="/pm/change-requests" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-arrow-left-right me-2"></i>Change Requests
          </NuxtLink>
        </li>
        <li class="nav-item">
          <NuxtLink to="/pm/tasks" class="nav-link text-white-50 rounded px-2 py-1" active-class="bg-white bg-opacity-10 text-white">
            <i class="bi bi-list-task me-2"></i>Задачи
          </NuxtLink>
        </li>
      </ul>

      <div class="border-top border-white border-opacity-10 pt-3 mt-2">
        <NuxtLink to="/logout" class="nav-link text-white-50 rounded px-2 py-1">
          <i class="bi bi-box-arrow-right me-2"></i>Выйти
        </NuxtLink>
      </div>
    </nav>

    <main class="flex-grow-1 bg-light p-4" style="min-height: 100vh;">
      <slot />
    </main>
  </div>
</template>
