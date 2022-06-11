package forge

var (
	forgeToken string
	repo       string
	branch     string
)

func SetBranch(name string) {
	branch = name
}

func GetBranch() string {
	return branch
}

func SetRepo(name string) {
	repo = name
}

func GetRepo() string {
	return repo
}

func SetForgeToken(token string) {
	forgeToken = token
}

func GetForgeToken() string {
	return forgeToken
}
