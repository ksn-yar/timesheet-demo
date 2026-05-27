import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('Timesheet / Тикеты', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/ts/tickets')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Тикеты' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('кнопка "Добавить тикет" видна', async ({ page }) => {
    await expect(page.getByRole('button', { name: /добавить тикет/i })).toBeVisible()
  })

  test('блок фильтров отображается', async ({ page }) => {
    await expect(page.locator('input[type="date"]').first()).toBeVisible()
  })

  test('открытие модала создания тикета', async ({ page }) => {
    await page.getByRole('button', { name: /добавить тикет/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await expect(page.locator('.modal-title')).toContainText('Новый тикет')
  })

  test('создать тикет без задачи → показывается ошибка', async ({ page }) => {
    await page.getByRole('button', { name: /добавить тикет/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('закрыть модал по кнопке Отмена', async ({ page }) => {
    await page.getByRole('button', { name: /добавить тикет/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-secondary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
  })

  test('поле даты по умолчанию заполнено сегодняшней датой', async ({ page }) => {
    await page.getByRole('button', { name: /добавить тикет/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    const today = new Date().toISOString().slice(0, 10)
    const dateVal = await page.locator('.modal-content input[type="date"]').inputValue()
    expect(dateVal).toBe(today)
  })

  test('сброс фильтров очищает поля', async ({ page }) => {
    const dateInput = page.locator('input[type="date"]').first()
    await dateInput.fill('2026-01-01')
    await page.getByRole('button', { name: /сбросить/i }).click()
    await expect(dateInput).toHaveValue('')
  })
})
