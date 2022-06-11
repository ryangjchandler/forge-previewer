package cmd

import (
	"log"
	"os"

	"github.com/urfave/cli/v2"
)

func Run() {
	app := &cli.App{
		Name:        "forge-previewer",
		Description: "Create preview deployments for pull request with Laravel Forge.",
		Version:     GetCurrentVersion(),
		Commands: []*cli.Command{
			GetDeployCommand(),
		},
	}

	err := app.Run(os.Args)
	if err != nil {
		log.Fatal(err)
	}
}
