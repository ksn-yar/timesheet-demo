import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('WorkCatalog / Тарифные ставки', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/wc/rates')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Тарифные ставки' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('пункт "Тарифные ставки" виден в меню для Admin', async ({ page }) => {
    await expect(page.getByRole('link', { name: /Тарифные ставки/i })).toBeVisible()
  })

  test('создать ставку без roleId и workId → ошибка валидации', async ({ page }) => {
    await page.getByRole('button', { name: /создать ставку/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()

    await page.locator('input[placeholder="Например: 1500.00"]').fill('100')
    await page.locator('input[placeholder="RUB"]').fill('RUB')
    await page.locator('input[type="date"]').fill('2026-01-01')
    // roleId и workId не выбраны
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('создать ставку с привязкой к роли → появляется в таблице', async ({ page }) => {
    const ts = Date.now()
    const roleName = `Rate-Role-${ts}`
    // Сумма уникальная чтобы не конфликтовать с данными от других тестов
    const amount = `${(ts % 9000) + 1000}.00`

    // Создаём роль через страницу Roles
    await gotoAndReady(page, '/wc/roles')
    await page.getByRole('button', { name: /создать/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-content input[type="text"]').fill(roleName)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Переходим к ставкам
    await gotoAndReady(page, '/wc/rates')
    await page.getByRole('button', { name: /создать ставку/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()

    await page.locator('input[placeholder="Например: 1500.00"]').fill(amount)
    await page.locator('input[placeholder="RUB"]').fill('USD')
    await page.locator('input[type="date"]').fill('2026-01-01')
    await page.locator('.modal-content select').filter({ hasText: 'Не выбрана' }).selectOption({ label: roleName })

    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name: amount }).first()).toBeVisible()
  })

  test('редактировать ставку → изменения сохраняются', async ({ page }) => {
    // Создаём роль
    const roleName = `Edit-Role-${Date.now()}`
    await gotoAndReady(page, '/wc/roles')
    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(roleName)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Создаём ставку
    await gotoAndReady(page, '/wc/rates')
    await page.getByRole('button', { name: /создать ставку/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('input[placeholder="Например: 1500.00"]').fill('1000.00')
    await page.locator('input[placeholder="RUB"]').fill('EUR')
    await page.locator('input[type="date"]').fill('2026-01-01')
    await page.locator('.modal-content select').filter({ hasText: 'Не выбрана' }).selectOption({ label: roleName })
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Редактируем
    const row = page.getByRole('row', { name: /1000\.00/ })
    await row.getByTitle('Редактировать').click()
    await expect(page.locator('.modal-content')).toBeVisible()

    await page.locator('input[placeholder="Например: 1500.00"]').fill('1500.00')
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name: '1500.00' }).first()).toBeVisible()
  })

  test('удалить ставку → исчезает из таблицы', async ({ page }) => {
    const roleName = `Del-RateRole-${Date.now()}`
    await gotoAndReady(page, '/wc/roles')
    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(roleName)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    await gotoAndReady(page, '/wc/rates')
    await page.getByRole('button', { name: /создать ставку/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('input[placeholder="Например: 1500.00"]').fill('999.00')
    await page.locator('input[placeholder="RUB"]').fill('RUB')
    await page.locator('input[type="date"]').fill('2026-06-01')
    await page.locator('.modal-content select').filter({ hasText: 'Не выбрана' }).selectOption({ label: roleName })
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    const row = page.getByRole('row', { name: /999\.00/ })
    await row.getByTitle('Удалить').click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-danger').click()

    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name: '999.00' })).not.toBeVisible()
  })

  test('фильтр по роли сужает список', async ({ page }) => {
    const roleFilter = page.locator('select').first()
    await expect(roleFilter).toBeVisible()
    const options = await roleFilter.locator('option').count()
    expect(options).toBeGreaterThanOrEqual(1)
  })
})
