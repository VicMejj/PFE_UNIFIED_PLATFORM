import uuid
from django.db import models
from django.utils import timezone

class TimeStampedModel(models.Model):
    """
    An abstract base class model that provides self-updating
    ``created_at`` and ``updated_at`` fields.
    """
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        abstract = True

class UUIDModel(models.Model):
    """
    An abstract base class model that makes the primary key `id` a UUID field.
    """
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)

    class Meta:
        abstract = True

class SoftDeleteModel(models.Model):
    """
    An abstract base class model with a deleted_at field.
    """
    deleted_at = models.DateTimeField(null=True, blank=True)

    def delete(self, using=None, keep_parents=False):
        """Soft delete the instance."""
        self.deleted_at = timezone.now()
        self.save(using=using)

    class Meta:
        abstract = True

class BaseModel(UUIDModel, TimeStampedModel, SoftDeleteModel):
    """
    A base model combining UUID primary key, timestamps, and soft delete functionality.
    """
    class Meta:
        abstract = True
