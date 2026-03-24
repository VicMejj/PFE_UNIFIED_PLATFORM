from django.core.management.base import BaseCommand

from ai_services.services.rag_store import RAGStore


class Command(BaseCommand):
    help = "Builds the RAG index from documentation sources."

    def handle(self, *args, **options):
        store = RAGStore()
        store.build_and_save()
        self.stdout.write(
            self.style.SUCCESS(
                f"RAG index built with {len(store.chunks)} chunks (method={store.method})."
            )
        )
