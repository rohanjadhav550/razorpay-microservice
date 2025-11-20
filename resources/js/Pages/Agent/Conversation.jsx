import { useState, useRef, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ERDiagramViewer } from '@/Components/ERDiagram';

export default function ConversationPage({ conversation, connections }) {
    const { flash } = usePage().props;
    const messagesEndRef = useRef(null);
    const [activeTab, setActiveTab] = useState('chat');
    const [isGenerating, setIsGenerating] = useState(false);
    const [generationStep, setGenerationStep] = useState(0);

    const { data, setData, post, processing, reset } = useForm({
        message: '',
    });

    const generationSteps = [
        { label: 'Analyzing conversation', description: 'Extracting database requirements from your messages...' },
        { label: 'Fetching schema', description: 'Getting current database structure...' },
        { label: 'Generating ER diagrams', description: 'Creating visual representations of schemas...' },
        { label: 'Creating migrations', description: 'Generating SQL migration statements...' },
        { label: 'Saving proposal', description: 'Storing the schema proposal...' },
    ];

    // Simulate progress through steps
    useEffect(() => {
        if (isGenerating) {
            const interval = setInterval(() => {
                setGenerationStep((prev) => {
                    if (prev < generationSteps.length - 1) {
                        return prev + 1;
                    }
                    return prev;
                });
            }, 3000); // Move to next step every 3 seconds

            return () => clearInterval(interval);
        } else {
            setGenerationStep(0);
        }
    }, [isGenerating]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [conversation.messages]);

    const handleSendMessage = (e) => {
        e.preventDefault();
        if (!data.message.trim()) return;

        post(`/agent/conversations/${conversation.id}/chat`, {
            onSuccess: () => reset(),
        });
    };

    const handleGenerateProposal = () => {
        setIsGenerating(true);
        setGenerationStep(0);
        router.post(`/agent/conversations/${conversation.id}/generate-proposal`, {}, {
            onSuccess: () => {
                setIsGenerating(false);
                setActiveTab('proposal');
            },
            onError: () => {
                setIsGenerating(false);
            },
            onFinish: () => {
                setIsGenerating(false);
            },
        });
    };

    const handleApproveProposal = (proposalId) => {
        router.post(`/agent/proposals/${proposalId}/approve`);
    };

    const handleRejectProposal = (proposalId) => {
        router.post(`/agent/proposals/${proposalId}/reject`);
    };

    const handleApplyMigrations = (proposalId) => {
        if (confirm('Are you sure you want to apply these migrations? This will modify your database.')) {
            router.post(`/agent/proposals/${proposalId}/apply`);
        }
    };

    const latestProposal = conversation.workflow?.schema_proposals?.[0];

    return (
        <AuthenticatedLayout fullWidth={true}>
            <Head title={`Conversation #${conversation.id}`} />

            <div className="flex flex-col h-[calc(100vh-4rem)]">
                {/* Header */}
                <div className="flex justify-between items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div className="flex items-center gap-6">
                        <div>
                            <h1 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Conversation #{conversation.id}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {conversation.database_connection?.name || 'No database selected'}
                            </p>
                        </div>

                        {/* Tab Navigation */}
                        <div className="flex border-b border-gray-200 dark:border-gray-700">
                            <button
                                onClick={() => setActiveTab('chat')}
                                className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px ${
                                    activeTab === 'chat'
                                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                                }`}
                            >
                                Chat
                            </button>
                            <button
                                onClick={() => setActiveTab('proposal')}
                                className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px flex items-center gap-2 ${
                                    activeTab === 'proposal'
                                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                                }`}
                            >
                                Proposal
                                {latestProposal && (
                                    <span className={`px-2 py-0.5 text-xs rounded-full ${
                                        latestProposal.status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                            : latestProposal.status === 'approved'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                            : latestProposal.status === 'applied'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                    }`}>
                                        {latestProposal.status}
                                    </span>
                                )}
                            </button>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <a
                            href="/agent"
                            className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Back
                        </a>
                        {conversation.database_connection_id && conversation.messages?.length > 0 && (
                            <button
                                onClick={handleGenerateProposal}
                                className="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700"
                            >
                                Generate Proposal
                            </button>
                        )}
                    </div>
                </div>

                {flash?.success && (
                    <div className="mx-4 mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                        <p className="text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </p>
                    </div>
                )}

                {/* Chat Tab Content */}
                {activeTab === 'chat' && (
                    <>
                        {/* Messages */}
                        <div className="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                            {!conversation.messages || conversation.messages.length === 0 ? (
                                <div className="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                    <div className="text-center">
                                        <p className="text-lg mb-2">Start a conversation</p>
                                        <p className="text-sm">
                                            Describe your database requirements and the AI will help design the schema.
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col">
                                    {conversation.messages.map((message, index) => (
                                        <div
                                            key={index}
                                            className={`w-full py-6 ${
                                                message.role === 'user'
                                                    ? 'bg-white dark:bg-gray-800'
                                                    : 'bg-gray-50 dark:bg-gray-900'
                                            }`}
                                        >
                                            <div className="max-w-3xl mx-auto px-4">
                                                <div className="flex gap-4">
                                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${
                                                        message.role === 'user'
                                                            ? 'bg-indigo-600 text-white'
                                                            : 'bg-green-600 text-white'
                                                    }`}>
                                                        {message.role === 'user' ? 'U' : 'AI'}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <div className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap break-words">
                                                            {message.content}
                                                        </div>
                                                        <div className="text-xs mt-2 text-gray-500 dark:text-gray-400">
                                                            {new Date(message.timestamp).toLocaleTimeString()}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    <div ref={messagesEndRef} />
                                </div>
                            )}
                        </div>

                        {/* Input */}
                        <div className="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                            <form onSubmit={handleSendMessage} className="max-w-3xl mx-auto">
                                <div className="flex gap-3">
                                    <input
                                        type="text"
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        placeholder="Describe your database requirements..."
                                        className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                        disabled={processing}
                                    />
                                    <button
                                        type="submit"
                                        disabled={processing || !data.message.trim()}
                                        className="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        {processing ? 'Sending...' : 'Send'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </>
                )}

                {/* Proposal Tab Content */}
                {activeTab === 'proposal' && (
                    <div className="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
                        {!latestProposal ? (
                            <div className="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                <div className="text-center">
                                    <p className="text-lg mb-2">No proposal yet</p>
                                    <p className="text-sm mb-4">
                                        Have a conversation about your database requirements, then click "Generate Proposal".
                                    </p>
                                    <button
                                        onClick={() => setActiveTab('chat')}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                    >
                                        Go to Chat
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="max-w-4xl mx-auto space-y-6">
                                {/* Proposal Status & Actions */}
                                <div className={`p-4 rounded-lg ${
                                    latestProposal.status === 'pending'
                                        ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'
                                        : latestProposal.status === 'approved'
                                        ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'
                                        : latestProposal.status === 'applied'
                                        ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                                        : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600'
                                }`}>
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <h3 className="font-medium text-gray-900 dark:text-white">
                                                Schema Proposal
                                            </h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Status: <span className="capitalize font-medium">{latestProposal.status}</span>
                                            </p>
                                        </div>
                                        <div className="flex gap-2">
                                            {latestProposal.status === 'pending' && (
                                                <>
                                                    <button
                                                        onClick={() => handleApproveProposal(latestProposal.id)}
                                                        className="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        onClick={() => handleRejectProposal(latestProposal.id)}
                                                        className="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700"
                                                    >
                                                        Reject
                                                    </button>
                                                </>
                                            )}
                                            {latestProposal.status === 'approved' && (
                                                <button
                                                    onClick={() => handleApplyMigrations(latestProposal.id)}
                                                    className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                                >
                                                    Apply Migrations
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Current Schema (from proposed_changes JSON) */}
                                {latestProposal.proposed_changes && (
                                    <ERDiagramViewer
                                        schema={latestProposal.proposed_changes}
                                        title="Current Database Schema"
                                    />
                                )}

                                {/* Proposed ER Diagram (from AI-generated Mermaid) */}
                                {latestProposal.er_diagram && (
                                    <div className="space-y-4">
                                        <ERDiagramViewer
                                            mermaid={latestProposal.er_diagram}
                                            title="Proposed Schema Changes"
                                        />

                                        {/* Mermaid source code (collapsible) */}
                                        <details className="bg-white dark:bg-gray-800 rounded-lg shadow">
                                            <summary className="px-4 py-3 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                                                View Mermaid Source
                                            </summary>
                                            <pre className="px-4 pb-4 overflow-x-auto text-xs text-gray-600 dark:text-gray-400 font-mono">
                                                {latestProposal.er_diagram}
                                            </pre>
                                        </details>
                                    </div>
                                )}

                                {/* Migration SQL */}
                                {latestProposal.migrations && (
                                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-3">
                                            Migration SQL
                                        </h4>
                                        {typeof latestProposal.migrations === 'string' ? (
                                            <pre className="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg overflow-x-auto text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                                                {latestProposal.migrations}
                                            </pre>
                                        ) : (
                                            <div className="space-y-3">
                                                {Array.isArray(latestProposal.migrations) && latestProposal.migrations.map((migration, index) => (
                                                    <div key={index} className="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                                        <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                            {migration.name || `Migration ${index + 1}`}
                                                        </p>
                                                        <pre className="text-sm overflow-x-auto text-gray-800 dark:text-gray-200">
                                                            {migration.sql}
                                                        </pre>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Description */}
                                {latestProposal.description && (
                                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-3">
                                            Requirements Analysis
                                        </h4>
                                        <div className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                            {latestProposal.description}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Generation Loading Overlay */}
            {isGenerating && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4 shadow-xl">
                        <div className="text-center mb-6">
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-full mb-4">
                                <svg className="w-8 h-8 text-indigo-600 dark:text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Generating Proposal
                            </h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Please wait while we analyze your requirements...
                            </p>
                        </div>

                        <div className="space-y-3">
                            {generationSteps.map((step, index) => (
                                <div
                                    key={index}
                                    className={`flex items-start gap-3 p-3 rounded-lg transition-all ${
                                        index === generationStep
                                            ? 'bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800'
                                            : index < generationStep
                                            ? 'bg-green-50 dark:bg-green-900/30'
                                            : 'bg-gray-50 dark:bg-gray-700/50'
                                    }`}
                                >
                                    <div className={`w-6 h-6 rounded-full flex items-center justify-center shrink-0 ${
                                        index === generationStep
                                            ? 'bg-indigo-600 text-white'
                                            : index < generationStep
                                            ? 'bg-green-600 text-white'
                                            : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'
                                    }`}>
                                        {index < generationStep ? (
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                            </svg>
                                        ) : index === generationStep ? (
                                            <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        ) : (
                                            <span className="text-xs">{index + 1}</span>
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className={`text-sm font-medium ${
                                            index === generationStep
                                                ? 'text-indigo-900 dark:text-indigo-100'
                                                : index < generationStep
                                                ? 'text-green-900 dark:text-green-100'
                                                : 'text-gray-500 dark:text-gray-400'
                                        }`}>
                                            {step.label}
                                        </p>
                                        {index === generationStep && (
                                            <p className="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">
                                                {step.description}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
