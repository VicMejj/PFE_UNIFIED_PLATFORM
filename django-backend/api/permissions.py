from rest_framework.permissions import BasePermission


class IsAuthenticated(BasePermission):
    """
    Allows access only to authenticated users (i.e., valid JWT token was decoded).
    """
    def has_permission(self, request, view):
        return request.user is not None and isinstance(request.user, dict)


class IsAdminOrHR(BasePermission):
    """
    Allows access to admins and HR roles only.
    The roles are expected in the JWT payload as 'roles' list.
    """
    ALLOWED_ROLES = ['admin', 'hr', 'super-admin']

    def has_permission(self, request, view):
        if not request.user or not isinstance(request.user, dict):
            return False
        user_roles = request.user.get('roles', [])
        return any(r in self.ALLOWED_ROLES for r in user_roles)


class IsManagerOrAbove(BasePermission):
    """
    Allows access to managers, HR, and admins.
    """
    ALLOWED_ROLES = ['admin', 'hr', 'manager', 'super-admin']

    def has_permission(self, request, view):
        if not request.user or not isinstance(request.user, dict):
            return False
        user_roles = request.user.get('roles', [])
        return any(r in self.ALLOWED_ROLES for r in user_roles)
