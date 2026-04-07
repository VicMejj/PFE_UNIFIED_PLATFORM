import { djangoApi, unwrapResponse, unwrapItems } from '@/api/http'

export const djangoNotificationsApi = {
  async getNotifications() {
    const response = await djangoApi.get('/notifications/')
    return unwrapItems(unwrapResponse(response))
  }
}
